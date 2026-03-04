#!/usr/bin/env python3
import argparse
import json
import math
import re
from datetime import datetime, timezone
from typing import Dict, List, Any, Tuple


def load_json(path: str) -> Dict[str, Any]:
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def price_bucket(price: float) -> str:
    if price < 50:
        return "low"
    if price < 200:
        return "mid"
    return "high"


def norm_text(value: str) -> str:
    value = (value or "").lower().strip()
    value = re.sub(r"[^\w\s]", " ", value, flags=re.UNICODE)
    value = re.sub(r"\s+", " ", value, flags=re.UNICODE)
    return value.strip()


def tokenize(value: str) -> List[str]:
    text = norm_text(value)
    if not text:
        return []
    tokens = [t for t in text.split(" ") if len(t) >= 3]
    return list(dict.fromkeys(tokens))


def vectorize_product(product: Dict[str, Any]) -> Dict[str, float]:
    vector: Dict[str, float] = {}
    category = norm_text(str(product.get("categorie", "")))
    ptype = norm_text(str(product.get("type", "")))
    is_promo = bool(product.get("isPromotion", False))
    price = float(product.get("effectivePrice", 0.0) or 0.0)

    if category:
        vector[f"cat:{category}"] = 1.0
    if ptype:
        vector[f"type:{ptype}"] = 1.0
    if is_promo:
        vector["promo:1"] = 1.0
    if price > 0:
        vector[f"price:{price_bucket(price)}"] = 1.0

    return vector


def merge_vector(target: Dict[str, float], source: Dict[str, float], weight: float) -> None:
    for key, value in source.items():
        target[key] = target.get(key, 0.0) + (value * weight)


def cosine_similarity(vec_a: Dict[str, float], vec_b: Dict[str, float]) -> float:
    if not vec_a or not vec_b:
        return 0.0

    dot = 0.0
    norm_a = 0.0
    norm_b = 0.0

    for key, val in vec_a.items():
        norm_a += val * val
        dot += val * vec_b.get(key, 0.0)

    for val in vec_b.values():
        norm_b += val * val

    if norm_a <= 0 or norm_b <= 0:
        return 0.0

    return dot / (math.sqrt(norm_a) * math.sqrt(norm_b))


def recency_bonus(created_at: str) -> float:
    if not created_at:
        return 0.0
    try:
        created_dt = datetime.fromisoformat(created_at.replace("Z", "+00:00"))
        now = datetime.now(timezone.utc)
        if created_dt.tzinfo is None:
            created_dt = created_dt.replace(tzinfo=timezone.utc)
        age_days = max(0, (now - created_dt).days)
        return float(max(0, 8 - min(8, age_days)))
    except Exception:
        return 0.0


def proximity_bonus(user_address: str, product_address: str) -> float:
    u = norm_text(user_address)
    p = norm_text(product_address)
    if not u or not p:
        return 0.0

    if u == p:
        return 10.0
    if u in p or p in u:
        return 7.0

    ut = set(tokenize(u))
    pt = set(tokenize(p))
    common = len(ut.intersection(pt))
    if common >= 2:
        return 5.0
    if common == 1:
        return 2.0
    return 0.0


def query_bonus(query: str, name: str, description: str) -> float:
    q = norm_text(query)
    if not q:
        return 0.0
    n = norm_text(name)
    d = norm_text(description)
    if q in n:
        return 8.0
    if q in d:
        return 4.0
    return 0.0


def build_profile(interactions: List[Dict[str, Any]]) -> Dict[str, float]:
    profile: Dict[str, float] = {}
    for item in interactions:
        weight = float(item.get("weight", 1.0) or 1.0)
        product = item.get("product", {})
        vec = vectorize_product(product)
        merge_vector(profile, vec, weight)
    return profile


def score_products(payload: Dict[str, Any]) -> List[Tuple[int, float]]:
    filters = payload.get("filters", {}) or {}
    query = str(filters.get("q", "") or "")
    user_address = str(payload.get("user", {}).get("address", "") or "")
    interactions = payload.get("interactions", []) or []
    candidates = payload.get("candidates", []) or []

    profile = build_profile(interactions)
    scored: List[Tuple[int, float]] = []

    for product in candidates:
        pid = int(product.get("id", 0) or 0)
        if pid <= 0:
            continue

        vec = vectorize_product(product)
        score = cosine_similarity(profile, vec) * 100.0
        if bool(product.get("isPromotion", False)):
            score += 5.0

        score += recency_bonus(str(product.get("createdAt", "") or ""))
        score += proximity_bonus(user_address, str(product.get("locationAddress", "") or ""))
        score += query_bonus(query, str(product.get("nom", "") or ""), str(product.get("description", "") or ""))

        scored.append((pid, score))

    scored.sort(key=lambda x: x[1], reverse=True)
    return scored


def main() -> None:
    parser = argparse.ArgumentParser(description="Local ML marketplace recommender")
    parser.add_argument("--input", required=True, help="Path to input JSON")
    parser.add_argument("--top", type=int, default=3, help="Top N product ids")
    args = parser.parse_args()

    payload = load_json(args.input)
    ranked = score_products(payload)
    top_ids = [pid for pid, _ in ranked[: max(1, args.top)]]

    print(json.dumps({"productIds": top_ids}, ensure_ascii=False))


if __name__ == "__main__":
    main()
