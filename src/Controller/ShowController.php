<?php

namespace App\Controller;

use App\Entity\User;
<<<<<<< HEAD
=======
use App\Entity\Campus;
>>>>>>> 7850a289226aa4cd42044c0d82b9ca8c338036d4
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/view')]

class ShowController extends AbstractController
{
 
    #[Route('/{username}', name: 'app_admin_user_show')]
    public function show(User $user, Campus $campus)
    {
      


        return $this->render('admin_user/student_dashboard.html.twig', [
            'user' => $user,
            
            
        ]);
    }

}