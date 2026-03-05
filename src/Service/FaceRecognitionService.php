<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;

class FaceRecognitionService
{
    public function __construct(
        private string          $uploadDir,
        private string          $projectDir,
        private LoggerInterface $logger,
    ) {}

    /** @return array{match: bool, score: float, message: string} */
    public function verifyFace(User $user, string $capturedImageBase64): array
    {
        if (!$user->getImage()) {
            return ['match' => false, 'score' => 0.0, 'message' => 'No profile photo on file'];
        }

        $possiblePaths = [
            $this->uploadDir . '/users/images/' . $user->getImage(),
            $this->uploadDir . '/images/' . $user->getImage(),
            $this->uploadDir . '/' . $user->getImage(),
        ];

        $storedImagePath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $storedImagePath = $path;
                break;
            }
        }

        if (!$storedImagePath) {
            return ['match' => false, 'score' => 0.0, 'message' => 'Profile photo not found'];
        }

        $this->logger->info('Using stored image: ' . $storedImagePath);

        $sanitizedBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $capturedImageBase64);
        if (!is_string($sanitizedBase64)) {
            return ['match' => false, 'score' => 0.0, 'message' => 'Invalid image data'];
        }

        $imageData = base64_decode($sanitizedBase64, true);

        if (!$imageData) {
            return ['match' => false, 'score' => 0.0, 'message' => 'Invalid image data'];
        }

        $tempPath = sys_get_temp_dir() . '/face_capture_' . uniqid() . '.jpg';
        file_put_contents($tempPath, $imageData);

        try {
            $result = $this->runInference($storedImagePath, $tempPath);
        } finally {
            if (file_exists($tempPath)) unlink($tempPath);
        }

        $this->logger->info('Face result', [
            'user'  => $user->getEmail(),
            'score' => $result['score'],
            'match' => $result['match'],
        ]);

        return $result;
    }

    /** @return array{match: bool, score: float, message: string} */
    private function runInference(string $storedPath, string $capturedPath): array
    {
        $scriptPath = $this->projectDir . '/var/ml_models/face_inference.py';

        if (!file_exists($scriptPath)) {
            return ['match' => false, 'score' => 0.0, 'message' => 'Script not found'];
        }

        // ✅ Fix Windows backslashes
        $storedPath   = str_replace('\\', '/', $storedPath);
        $capturedPath = str_replace('\\', '/', $capturedPath);
        $scriptPath   = str_replace('\\', '/', $scriptPath);

        // ✅ Windows uses 'python', Linux/Mac uses 'python3'
        $pythonCmd = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';

        $cmd = sprintf(
            '%s %s %s %s',
            $pythonCmd,
            escapeshellarg($scriptPath),
            escapeshellarg($storedPath),
            escapeshellarg($capturedPath)
        );

        $this->logger->info('Running: ' . $cmd);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            return ['match' => false, 'score' => 0.0, 'message' => 'Failed to start Python'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        if ($stderr) {
            $this->logger->warning('Python stderr: ' . $stderr);
        }

        $this->logger->info('Python stdout: ' . trim($stdout));

        $decoded = json_decode(trim($stdout), true);

        if (!$decoded) {
            return ['match' => false, 'score' => 0.0, 'message' => 'Bad output: ' . $stdout];
        }

        if (isset($decoded['error'])) {
            return ['match' => false, 'score' => 0.0, 'message' => $decoded['error']];
        }

        return [
            'match'   => (bool)  $decoded['match'],
            'score'   => (float) $decoded['score'],
            'message' => (string) $decoded['message'],
        ];
    }
}