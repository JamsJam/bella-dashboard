<?php

namespace App\Factory\Collections;

use App\Entity\Clothes\Clothes;
use App\Entity\Category\Category;
use App\Entity\Collections\Collections;
use App\Factory\Clothes\ClothesFactory;
use App\DTO\Clothes\Collections\CollectionsDTO;
use App\Service\FileService\FileUploaderService;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CollectionsFactory 
{

    public function __construct(
        private ClothesFactory $clothesFactory,
        private FileUploaderService $fileUploader,
        private SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/upload/clothes/collection')] private string $targetDirectory,
    ){}

    /**
     * create a Collection
     *
     * @param CollectionsDTO $collection
     * @return Collections
     */
    public function createCollection(
        CollectionsDTO $dto,
        bool $fromCategory = false,
        array $selectedClotheImage = []
        // 
        ): Collections
    {
        //?------$name
        $name = $dto->getName();

        //?------images
        $image = $this->getImagePath($dto->getImage(),$dto->getName());

        //?------ clothes

        /** @var Clothes[] $clothes */
        $clothes = $this->clothesFactory->createManyClothes($dto->getClothes(),true, null, $selectedClotheImage );

        //?------ clothes

        /** @var Category $category */
        $category = $dto->getCategory();

        $today = new \DateTimeImmutable();

        $collection = new Collections();
        $collection
            ->setName($name)
            ->setCategory($category)
            ->setImage($image)
            ->setSizeguid(null) 
            ->setIsOnline(false) 
            ->setCreatedAt($today)
            ->setEditedAt($today)
        ;

        foreach($clothes as $clothe){
            $collection->addClothes($clothe);
        }

        return $collection;
    }

    /**
     *  create many Collection
     *
     * @param CollectionsDTO[] $collections
     * @return Collections[]
     */
    public function createManyCollections(array $collections, bool $fromCategory = false, array $selectedClotheImage = []) : array
    {

        $arrayCollection = [] ;

        foreach($collections as $collection){
            $arrayCollection[] = $this->createCollection($collection,$fromCategory, $selectedClotheImage);
        };

        return $arrayCollection;
    }

    /**
     * Get collection's image path
     *
     * @param UploadedFile $image
     * @param string $collectionName
     * @return string
     */
    private function getImagePath(UploadedFile $image,string $collectionName): string
    {

        $imageName = $this->fileUploader->upload($image, $this->getImageTargetDirectory(), $this->slugger->slug($collectionName));
        $imagePath = explode('public/',$this->getImageTargetDirectory())[1]."/".$this->slugger->slug($collectionName)."/".$imageName;

        return $imagePath;
    }

    /**
     * Get collection's image's directory path
     *
     * @return string
     */
    private function getImageTargetDirectory():string
    {
        return $this->targetDirectory;
    }




}
