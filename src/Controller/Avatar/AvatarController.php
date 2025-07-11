<?php

namespace App\Controller\Avatar;

use App\DTO\Avatar\hairImageDTO;
use App\Enum\Avatar\BodyPartEnum;
use App\Service\UploadedFileService;
use App\Entity\Avatar\Body\Morphotype;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\Breadcrumb\BreadcrumbItemDTO;
use App\Resolver\Avatar\BodyPartNameResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Resolver\Avatar\BodyPartRegistryResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class AvatarController extends AbstractController
{
    #[Route('/avatar', name: 'app_avatar_index')]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        ?BodyPartEnum $type,
        EntityManagerInterface $entityManagerInterface,
        BodyPartRegistryResolver $bodyPartRegistryResolver
    ): Response {

        // ? ==== define type
        $type = BodyPartEnum::tryFrom($request->query->get('type'));
        $parts = null;
        if (BodyPartEnum::SKIN === $type) {
            $type = null;
        }



        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        if (null !== $type) {
            $metaData->addBreadcrumb((new BreadcrumbItemDTO())
            ->setTitle($type->value)
            ->setRoute('app_avatar_index')
            );

            $entity = $bodyPartRegistryResolver->getEntity('body',$type->value);
            $filterEntities =  $bodyPartRegistryResolver->getFilters($type->value);

            $filterItems = [];
            $usedFilters = [] ;
            // dd($request->query);
            foreach ($filterEntities as $key => $value) {
                $filterItems[$key] = $entityManagerInterface->getRepository($value)->findAll();
                if($request->query->all($key)){
                    $usedFilters[$key] = $request->query->all($key);
                }
                
            }
            // dd($usedFilters);



            $allPart = [];
            if ($usedFilters === []) {
                $allPart = $entityManagerInterface->getRepository($entity)->findAll();
            } else {
                $normalizedFilters = [];
                $filterMap = [
                    'colorFilter'      => 'color',
                    'shapeFilter'      => 'shape',
                    'skincolorFilter'  => 'skincolor',
                    'morphologieFilter'=> 'morphologie',
                    'morphotypeFilter' => 'morphotype',
                    'clothesFilter'    => 'clothes',
                    'collectionsFilter'=> 'collections',
                ];
                foreach ($usedFilters as $key => $values) {
                    if (isset($filterMap[$key])) {
                        $normalizedFilters[$filterMap[$key]] = $values;
                    }
                }
                // dd(...$normalizedFilters);
                $allPart = $entityManagerInterface->getRepository($entity)->findAllByFilters(...$normalizedFilters);
            }
            // dd($filterItems);

            // type!=null (Avatar bodypart index)
            return $this->render('avatar/index.html.twig', [
                'metaData' => $metaData,
                'type' => $type->value,
                'parts' => $parts,
                'bodyPartItems' => $allPart,
                'filters' => $filterItems,
            ]);
        }

        $typeChoice =  [
            'body' => 'corps',
            'face' => 'visage',
            'nose' => 'nez',
            'hair' => 'cheveux',
            'mouth' => 'bouche',
            'eye' => 'yeux',
            'eyebrows' => 'sourcils',
        ];
        // type=null (Avatar Hub)
        return $this->render('avatar/index.html.twig', [
            'metaData' => $metaData,
            'type' => $type,
            'typeChoice' => $typeChoice,
            'parts' => $parts,
        ]);
    }

    #[Route('/avatar/ajouter-des-partie-d-avatar', name: 'app_avatar_new')]
    public function new(
        PageMetadataProvider $pageMetadata,
        Request $request,
    ):Response
    {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));
        return $this->render('avatar/new.html.twig', [
            'metaData' => $metaData,
        ]);
    }

    #[Route('/avatar/upload', name: 'app_avatar_upload', methods: 'POST')]
    public function upload(
        Request $request,
        BodyPartNameResolver $bodyPartNameResolver,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        UploadedFileService $uploadFileService,
        EntityManagerInterface $entityManagerInterface
    ):Response
    {
        /**
         * @var UploadedFile 
         */
        $file = $request->files->get('fileData'); 
        $submittedToken= $request->request->get('token');
        if(!$file){
            return new JsonResponse("No file receives" , 400);
        }
       
        if (!$this->isCsrfTokenValid('add-body-part', $submittedToken)) {
            return new JsonResponse("action forbiden" , 401);
        }

        $fileName= (string) $file->getClientOriginalName();
        $nameKey = BodyPartEnum::tryfrom(explode('__',$fileName)[0]);
        if ($nameKey === null || $nameKey === "skin") {
            return new JsonResponse("Invalid name key, submitted : ".$nameKey , 400);
        }

        $newFileDirectoryPath = (string) '';
        try {
            $newFileDirectoryPath = $bodyPartNameResolver->getFilepath($fileName);
            
            $fileEntity = $bodyPartRegistryResolver->getEntity('body',explode('__',$fileName)[0]);
            $secondaryFileEntity = $bodyPartRegistryResolver->getFilters(explode('__',$fileName)[0]);
            
            $uploadFileService->move($newFileDirectoryPath,$file);

        }catch (\InvalidArgumentException $th) {
            return new JsonResponse($th->getMessage(),RESPONSE::HTTP_BAD_REQUEST);
        }catch (\Exception $e)  {
            // Autre exception : 500 Internal Server Error
            // (vous pouvez logger ici)
            return new JsonResponse(
                ['error' => 'Une erreur est survenue.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }catch (FileException $e)  {
            // Autre exception : 500 Internal Server Error
            // (vous pouvez logger ici)
            return new JsonResponse(
                ['error' => 'Une erreur est survenue.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $tableElementCase = [
            'hair' => "hair",
            'mouth' => "color",
            'nose' => "skincolor",
            'eye' => "color",
            'eyebrows' => "color",
            'face' => "skincolor",
            'body' => "body",
        ];
        //?-----filepath
        $filepath = (string) explode('public/',$newFileDirectoryPath)[1]."/".$fileName;
        //? ======== check if exist
        $checkitem = $entityManagerInterface->getRepository($fileEntity)->findBy(["name" => $fileName]);
        if(count($checkitem) === 1){
            //? ======== change haire image
            if($tableElementCase[$nameKey->value]==="hair"){
                $image = (array) $checkitem[0]->getImages();
                $side = explode('__',$fileName)[3];

                $newImageHairs = (new hairImageDTO())
                    ->setFrontImage($image["frontImage"])
                    ->setBackImage($image["backImage"]);
                if($side === "back"){
                    $newImageHairs->setBackImage($filepath);
                }else if($side === "front"){
                    $newImageHairs->setFrontImage($filepath);
                };
                $today = new \DateTimeImmutable();
                $checkitem[0]->setImages($newImageHairs->toArray())
                ->setEditedAt($today);
                ;

                $entityManagerInterface->persist($checkitem[0]);
                $entityManagerInterface->flush();



                
            }
            // element already present, file already move and edit nothing todo
            return new JsonResponse($fileName." edited with success", 200);
        };

        //?--- separate in 4 case (hair, color, skincolor, body)
        $today = new \DateTimeImmutable();
        $checksum = substr(hash('sha512', $fileName),0,60);

        switch ($tableElementCase[$nameKey->value]) {
            case 'color':
                $newBodyPart = new $fileEntity();

                $newBodyPart->setImage($filepath)
                    ->setName($fileName)
                    ->setChecksum($checksum)
                    ;

                $colorEntity = $secondaryFileEntity['colorFilter'];
                $colorName = explode('__',$fileName)[1];
                $checkColor = $entityManagerInterface->getRepository($colorEntity)->findBy(["name" => $colorName]);

                $shapeEntity = $secondaryFileEntity['shapeFilter'];
                $shapeName = explode('__',$fileName)[2];
                $checkShape = $entityManagerInterface->getRepository($shapeEntity)->findBy(["name" => $shapeName]);

                if(count($checkColor) === 1 ){

                    $newBodyPart->setColor($checkColor[0]);
                }else{
                    $newColor = new $colorEntity();
                    $newColor->setName($colorName)
                        ->setCreatedAt($today)
                        ->setEditedAt($today);
                    $entityManagerInterface->persist($newColor);
                    $newBodyPart->setColor($newColor);
                }
                if(count($checkShape) === 1){
                    $newBodyPart->setShape($checkShape[0]);
                }else{
                    $newShape = new $shapeEntity();
                    $newShape->setName($shapeName)
                        ->setCreatedAt($today)
                        ->setEditedAt($today)
                    ;
                    $entityManagerInterface->persist($newShape);
                    $newBodyPart->setShape($newShape);
                }
                $newBodyPart->setCreatedAt($today);
                $newBodyPart->setEditedAt($today);
                $entityManagerInterface->persist($newBodyPart);
                $entityManagerInterface->flush();

                break;
            case 'skincolor':
                $newBodyPart = new $fileEntity();

                $newBodyPart->setImage($filepath)
                    ->setName($fileName)
                    ->setChecksum($checksum)
                    ;

                $skincolorEntity = $secondaryFileEntity['skincolorFilter'];
                $skincolorName = explode('__',$fileName)[1];
                $checkSkincolor = $entityManagerInterface->getRepository($skincolorEntity)->findBy(["name" => $skincolorName]);

                // dd($nameKey,$fileName,$secondaryFileEntity);
                $shapeEntity = $secondaryFileEntity['shapeFilter'];
                $shapeName = explode('__',$fileName)[2];
                $checkShape = $entityManagerInterface->getRepository($shapeEntity)->findBy(["name" => $shapeName]);

                if(count($checkSkincolor) === 1 ){

                    $newBodyPart->setSkincolor($checkSkincolor[0]);
                }else{
                    $newSkincolor = new $skincolorEntity();
                    $newSkincolor->setName($skincolorName)
                        ->setCreatedAt($today)
                        ->setEditedAt($today)
                    ;
                    $entityManagerInterface->persist($newSkincolor);
                    $newBodyPart->setSkincolor($newSkincolor);
                }
                if(count($checkShape) === 1){
                    $newBodyPart->setShape($checkShape[0]);
                }else{
                    $newShape = new $shapeEntity();
                    $newShape->setName($shapeName)
                        ->setCreatedAt($today)
                        ->setEditedAt($today)
                    ;
                    $entityManagerInterface->persist($newShape);
                    $newBodyPart->setShape($newShape);
                }
                $newBodyPart->setCreatedAt($today);
                $newBodyPart->setEditedAt($today);
                $entityManagerInterface->persist($newBodyPart);
                $entityManagerInterface->flush();
                break;
            case 'hair':
                $newBodyPart = new $fileEntity();
                $newBodyPart->setName($fileName)
                ->setChecksum($checksum)
                ;

                //?-------hair image
                $side = explode('.',explode('__',$fileName)[3])[0];
                $newImageHairs = new hairImageDTO();
                if($side === "back"){
                    $newImageHairs->setBackImage($filepath);
                }else if($side === "front"){
                    $newImageHairs->setFrontImage($filepath);
                };
                $hairImages = $newImageHairs->toArray();
                $newBodyPart->setImages($hairImages);


                $colorEntity = $secondaryFileEntity['colorFilter'];
                $colorName = explode('__', $fileName)[1];
                $checkColor = $entityManagerInterface->getRepository($colorEntity)->findBy(["name" => $colorName]);

                $shapeEntity = $secondaryFileEntity['shapeFilter'];
                $shapeName = explode('__', $fileName)[2];
                $checkShape = $entityManagerInterface->getRepository($shapeEntity)->findBy(["name" => $shapeName]);

                if (count($checkColor) === 1) {
                    $newBodyPart->setColor($checkColor[0]);
                } else {
                    $newColor = new $colorEntity();
                    $newColor->setName($colorName)
                        ->setCreatedAt($today)
                        ->setEditedAt($today)
                    ;
                    $entityManagerInterface->persist($newColor);
                    $newBodyPart->setColor($newColor);
                }

                if (count($checkShape) === 1) {
                    $newBodyPart->setShape($checkShape[0]);
                } else {
                    $newShape = new $shapeEntity();
                    $newShape->setName($shapeName);
                    $newShape->setCreatedAt($today);
                    $newShape->setEditedAt($today);
                    $entityManagerInterface->persist($newShape);
                    $newBodyPart->setShape($newShape);
                }

                $newBodyPart->setCreatedAt($today);
                $newBodyPart->setEditedAt($today);
                $entityManagerInterface->persist($newBodyPart);
                $entityManagerInterface->flush();
                break;
            case 'body':

                $newBodyPart = new $fileEntity();

                //? ------- Image
                $newBodyPart->setImage($filepath)
                    ->setChecksum($checksum)
                    ->setName($fileName)
                ;

                //? ------- skincolor
                $skincolorEntity = $secondaryFileEntity['skincolorFilter'];
                $skincolorName = explode('__',$fileName)[1];
                $checkSkincolor = $entityManagerInterface->getRepository($skincolorEntity)->findBy(["name" => $skincolorName]);

                if(count($checkSkincolor) === 1 ){
                    $newBodyPart->setColor($checkSkincolor[0]);
                }else{
                    $newSkincolor = new $skincolorEntity();
                    $newSkincolor->setName($skincolorName)
                        ->setCreatedAt($today)
                        ->setEditedAt($today)
                    ;
                    $entityManagerInterface->persist($newSkincolor);
                    $newBodyPart->setSkincolor($newSkincolor);
                }

                //? ------- morphologie
                $morphologyEntity = $secondaryFileEntity['morphologieFilter'];
                $morphologyName = explode('__',$fileName)[2];
                $checkMorphology = $entityManagerInterface->getRepository($morphologyEntity)->findBy(["name" => $morphologyName]);
                $morphology = null;

                if(count($checkMorphology) === 1){
                    $newBodyPart->setName($checkMorphology[0]);
                    $morphology = $checkMorphology[0];
                }else{
                    $newMorphology = new $morphologyEntity();
                    $newMorphology->setName($morphologyName)
                        ->setCreatedAt($today)
                        ->setEditedAt($today)
                    ;
                    $entityManagerInterface->persist($newMorphology);
                    // $newBodyPart->setShape($newMorphology);
                    $morphology = $newMorphology;
                }


                //? ------- bodysize
                $bodysizeEntity = $secondaryFileEntity['morphotypeFilter'];
                $bodysizeName = explode('__',$fileName)[3];
                $checkBodysize = $entityManagerInterface->getRepository($bodysizeEntity)->findBy(["name" => $bodysizeName]);
                $bodysize = null;

                if(count($checkBodysize) === 1){
                    $bodysize = $checkBodysize[0];
                }else{
                    $newBodysize = new $bodysizeEntity();
                    $newBodysize->setName($bodysizeName)
                        ->setCreatedAt($today)
                        ->setEditedAt($today);
                    $entityManagerInterface->persist($newBodysize);
                    $bodysize = $newBodysize;
                }

                //? ------- vetement
                $clotheEntity = $secondaryFileEntity['clothesFilter'];
                $clotheName = explode('__',$fileName)[4];
                $checkClothe = $entityManagerInterface->getRepository($clotheEntity)->findBy(["name" => $clotheName]);

                                
                
                if(count($checkClothe) === 1){
                    $newBodyPart->setClothes($checkClothe);
                }else if($clotheName === '-none-'){
                    $newBodyPart->setClothes(null);
                }else{
                    return new JsonResponse($clotheName."N'existe pas en base de donnée",RESPONSE::HTTP_BAD_REQUEST);
                }

                //?------morphotype a partir de bodysize&checkMophology
                $morphotypeName = $bodysizeName."-".$morphologyName;
                $checkMorphotype = $entityManagerInterface->getRepository(Morphotype::class)->findBy(['size'=> $bodysize,'mophologie'=>$morphology]);
                if(count($checkMorphotype) === 1){
                    $newBodyPart->setMorphotype($checkMorphotype[0]);
                }else{
                    $newMorphotype = new Morphotype();
                    $newMorphotype->setName($morphotypeName);
                    $newMorphotype->setMorphologie($morphology);
                    $newMorphotype->setSize($bodysize)
                        ->setCreatedAt($today)
                        ->setEditedAt($today);
                    $entityManagerInterface->persist($newMorphotype);
                    $newBodyPart->setMorphotype($newMorphotype);
                }


                $newBodyPart->setCreatedAt($today);
                $newBodyPart->setEditedAt($today);
                $entityManagerInterface->persist($newBodyPart);
                $entityManagerInterface->flush();
                
                break;

        }
        



        return new JsonResponse($fileName, RESPONSE::HTTP_CREATED);
    }

    #[Route('/avatar/delete', name: 'app_avatar_delete', methods: 'POST')]
    public function delete(
        Request $request,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        EntityManagerInterface $entityManagerInterface,
        ):Response
    {
        $submittedToken = (string) $request->request->get('token');
        $idsToDelete = (array) json_decode($request->request->get('ids'));
        $bodyPart = (string) $request->request->get('bodyPart');
        // dd($request->request);

        if (!$this->isCsrfTokenValid('delete-part-avatar-token', $submittedToken) || BodyPartEnum::tryFrom($bodyPart) === null || $bodyPart === "skin") {
            return new JsonResponse(null , RESPONSE::HTTP_NOT_FOUND);
        }
        if(count($idsToDelete) === 0){
            return new JsonResponse("Aucun element à supprimer" , RESPONSE::HTTP_BAD_REQUEST);
        }

        $entity = $bodyPartRegistryResolver->getEntity('body',$bodyPart);
        
        try{

            $repository = $entityManagerInterface->getRepository($entity);
            foreach ($idsToDelete as $id) {
                $item = $repository->findOneBy(['id'=>$id]);
                if (!$item) {
                    return new JsonResponse('item non trouvé', RESPONSE::HTTP_BAD_REQUEST);
                }
                $entityManagerInterface->remove($item);
            }
            $entityManagerInterface->flush();
        }catch(\Throwable $e){
            return new JsonResponse($e->getMessage(),RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }




        return new JsonResponse('',RESPONSE::HTTP_NO_CONTENT);
    }
}
