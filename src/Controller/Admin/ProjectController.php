<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Form\TagInProjectForm;
use App\Form\TechnologiesInProjectForm;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/project')]
final class ProjectController extends AbstractController
{
    #[Route(name: 'app_admin_project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('admin/project/index.html.twig', [
            'projects' => $projectRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $session = $request->getSession();
        // todo make enumClass
        $validatedRule = [
            'generalData',
            'articleData',
            'default',
        ];
        $step = (int) $request->request->get('step', 1);

        // ? ==== Create form

        // $project = $project ?? new Project();

        if (1 === $step) {
            $form = $this->createFormBuilder(null, [
                // 'validation_groups' => $validatedRule[$step - 1],
                'allow_extra_fields' => true,
            ])

                ->add('title', TextType::class, [
                    'label' => 'Titre du projet',
                    'required' => false,
                    'row_attr' => [
                        'class' => 'form_wrapper',
                    ],
                    'constraints' => [
                        new Length(
                            min: 3,
                            minMessage: 'Entrer un titre d\'au moins 3 characteres',
                            max: 100,
                            maxMessage: 'Veuillez ne pas dépasser 100 characteres'
                        ),
                        new NotBlank(
                            message: 'Veuillez renseigner le titre du projet'
                        ),
                    ],
                ])
                ->add('description', TextareaType::class, [
                    'required' => false,
                    'row_attr' => [
                        'class' => 'form_wrapper',
                        'data-controller' => 'suneditor',
                    ],
                    'attr' => [
                        'data-suneditor-target' => 'sample',
                        // "id" => "editContainer"
                    ],
                    'constraints' => [
                        new Length(
                            min: 100,
                            minMessage: 'Entrer une description d\'au moins 100 characteres',
                            // max: 1000,
                            // maxMessage: 'Veuillez ne pas dépasser 1000 characteres'
                        ),
                        new NotBlank(
                            message: 'Veuillez renseigner la description du projet'
                        ),
                    ],
                ])
                ->add('technology', CollectionType::class, [
                    'entry_type' => TechnologiesInProjectForm::class,
                    'entry_options' => [
                        'label' => false,
                    ],
                    'label' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'required' => false,
                ])
                ->add('Images', FileType::class, [
                    'multiple' => true,
                    'row_attr' => [
                        'data-controller' => 'dropzone',
                        'class' => 'dropzone-container',
                    ],

                    'attr' => [
                        'class' => 'dropzone-input',
                        'data-dropzone-target' => 'input',
                        'data-action' => 'change->dropzone#onFileInputChange',
                    ],

                    'allow_extra_fields' => true,
                ])
                ->add('tags', CollectionType::class, [
                    'entry_type' => TagInProjectForm::class,
                    'entry_options' => [
                        'label' => false,
                    ],
                    'label' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                ])
                //  ->add('technologies',CollectionType::class,[])
                //  ->add('images',CollectionType::class)
                ->add('isOnline', CheckboxType::class, [
                    'label' => '...en ligne ?',
                    'row_attr' => [
                        'class' => 'form_wrapper',
                    ],
                    'required' => false,
                ])
                // ->add('projectlink', UrlType::class, [
                //     "row_attr" => [
                //         "class" => "form_wrapper"
                //     ]
                // ])
                ->add('isGitpublic', CheckboxType::class, [
                    'label' => '...sur Github ?',
                    'row_attr' => [
                        'class' => 'form_wrapper',
                    ],
                    'required' => false,
                ])
                // ->add('gitlink', UrlType::class, [
                //     "row_attr" => [
                //         "class" => "form_wrapper"
                //     ]
                // ])

                ->getForm();

        // $form->handleRequest($request);
        } elseif (2 === $step) {
            $form = $this->createFormBuilder(null, [
                // 'validation_groups' => $validatedRule[$step - 1]
            ])
                ->add('casestudy', TextareaType::class, [
                    'row_attr' => [
                        'class' => 'form_wrapper',
                    ],
                ])
                ->getForm();
        // $form->handleRequest($request);
        } elseif (3 === $step) {
            $form = $this->createFormBuilder(null, [
                // 'validation_groups' => $validatedRule[$step - 1]
            ])
                ->add('metaDescription', TextareaType::class, [
                    'mapped' => false,
                    'row_attr' => [
                        'class' => 'form_wrapper',
                    ],
                ])
                ->add('opengraph', CollectionType::class, [
                    'mapped' => false,
                    'row_attr' => [
                        'class' => 'form_wrapper',
                    ],
                ])

                ->getForm();
        }

        $form->handleRequest($request);

        // dd($form->getData());
        if ($form->isSubmitted() && $form->isValid()) {
            $project = $form->getData();
            // dd($project);
            if (1 === $step) {
                // register data in session
                $session->set('generalData', $project);

                ++$step;

                // render form new
                return $this->redirectToRoute('app_admin_project_new', [
                    'step' => $step,
                ]);
            }
            if (2 === $step) {
                // register data in session
                $session->set('articleData', $project);

                // render form new
                return $this->redirectToRoute('app_admin_project_new', [
                    'step' => $step + 1,
                ]);
            }
            if (3 === $step) {
                // validate data
                $projectSEO = $project;
                // todo validate and file yaml file

                // todo build project object
                $projectdata[] = $session->get('projectData');
                $projectdata[] = $session->get('articleData');

                $entityManager->persist($project);
                $entityManager->flush();

                $action = $request->request->get('action');
            }

            return $this->redirectToRoute('app_admin_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/project/new.html.twig', [
            // 'project' => $project,
            'form' => $form,
            'step' => $step,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        return $this->render('admin/project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder(null)
            // ? implement form
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_project_index', [], Response::HTTP_SEE_OTHER);
    }
}
