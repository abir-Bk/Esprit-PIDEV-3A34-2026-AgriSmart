<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class userController extends AbstractController
{
    #[Route('/users/dashboard', name: 'user_dashboard')]
    public function dashboard(Security $security)
    {
        $user = $security->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Render dashboard based on role
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_marketplace_dashboard');
        }
        return $this->render('front/semi-public/users/profile.html.twig');

       
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function list(
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {
        // Get filters from request
        $status = $request->query->get('status');
        $search = $request->query->get('search');
        $role = $request->query->get('role');
        $page = $request->query->getInt('page', 1);
        
        // Build query
        $queryBuilder = $userRepository->createQueryBuilder('u');
        
        // Apply status filter
        if ($status && in_array($status, ['active', 'pending', 'disabled'])) {
            $queryBuilder->andWhere('u.status = :status')
                        ->setParameter('status', $status);
        }
        
        // Apply search filter
        if ($search) {
            $queryBuilder->andWhere(
                'u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search'
            )->setParameter('search', '%' . $search . '%');
        }
        
        // Apply role filter
        if ($role && in_array($role, ['admin', 'employee', 'agriculteur', 'fournisseur'])) {
            $queryBuilder->andWhere('u.role = :role')
                        ->setParameter('role', $role);
        }
        
        // Order by creation date (newest first)
        $queryBuilder->orderBy('u.createdAt', 'DESC');
        
        // Paginate results
        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            5// items per page
        );

        return $this->render('back/admin/user_list.html.twig', [
            'users' => $pagination,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'currentRole' => $role,
        ]);
    }

    #[Route('/admin/users/{id}/status/{status}', name: 'admin_user_status')]
    public function changeStatus(
        int $id,
        string $status,
        UserRepository $repo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $user = $repo->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $allowed = ['active', 'pending', 'disabled'];

        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException('Invalid status');
        }

        $user->setStatus($status);
        $em->flush();

        $this->addFlash('success', 'User status updated successfully!');

        // Preserve filters when redirecting
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('admin_users');
    }
    
#[Route('/admin/users/excel', name: 'admin_users_excel')]
public function exportExcel(UserRepository $userRepository): Response
{
    $users = $userRepository->findAll();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'First Name');
    $sheet->setCellValue('C1', 'Last Name');
    $sheet->setCellValue('D1', 'Email');
    $sheet->setCellValue('E1', 'Role');
    $sheet->setCellValue('F1', 'Status');

    $row = 2;
    foreach ($users as $user) {
        $sheet->setCellValue('A'.$row, $user->getId());
        $sheet->setCellValue('B'.$row, $user->getFirstName());
        $sheet->setCellValue('C'.$row, $user->getLastName());
        $sheet->setCellValue('D'.$row, $user->getEmail());
        $sheet->setCellValue('E'.$row, $user->getRole());
        $sheet->setCellValue('F'.$row, $user->getStatus());
        $row++;
    }

    $writer = new Xlsx($spreadsheet);

    $fileName = 'users.xlsx';
    $temp_file = tempnam(sys_get_temp_dir(), $fileName);
    $writer->save($temp_file);

    return $this->file($temp_file, $fileName)->deleteFileAfterSend(true);
}
#[Route('/admin/users/pdf', name: 'admin_users_pdf')]
public function exportPdf(UserRepository $userRepository): Response
{
    $users = $userRepository->findAll();

    $html = $this->renderView('back/admin/users_pdf.html.twig', [
        'users' => $users,
    ]);

    $options = new Options();
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    return new Response($dompdf->stream('users.pdf', [
        'Attachment' => true
    ]));
}

    #[Route('/admin/users/{id}', name: 'admin_user_detail')]
    public function detail(int $id, UserRepository $repo): Response
    {
        $user = $repo->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('back/admin/user_detail.html.twig', [
            'user' => $user,
        ]);
    }

}