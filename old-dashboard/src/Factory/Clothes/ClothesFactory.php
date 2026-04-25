<?php

namespace App\Factory\Clothes;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\Clothescolor;
use App\DTO\Clothes\Clothes\ClothesDTO;
use App\Entity\Collections\Collections;
use App\Factory\Clothes\ClothecolorFactory;
use App\Service\FileService\FileUploaderService;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ClothesFactory 
{
    private \DateTimeImmutable $today;

    public function __construct(
        private FileUploaderService $fileUploader,
        private SluggerInterface $slugger,
        private ClothecolorFactory $clothecolorFactory,
        private ClothesizeFactory $clothesizeFactory,
        #[Autowire('%kernel.project_dir%/public/upload/clothes/clothes')] private string $targetDirectory,
        ?\DateTimeImmutable $today = null
    ){
        $this->today = $today ?? new \DateTimeImmutable();
    }

    /**
     * create a clothe
     *
     * @param ClothesDTO $clothes
     * @param bool $fromCollection
     * @param ?Collections $collection
     * @param ?int $selectedImage
     * @return Clothes
     */
    public function createClothe(ClothesDTO $dto, bool $fromCollection = false, ?Collections $collection, ?int $selectedImage ) :array
    {

        //?------ size
        $sizes = json_decode($dto->getSize());
        $clothes = $this->createClothesForEachSize($dto, $sizes, $collection, $selectedImage);

        return $clothes;
    }

    /**
     * Create many clothes
     *
     * @param ClothesDTO[] $clothes
     * @param bool $fromCollection
     * @param ?Collections $collection
     * @return Clothes[]
     */
    public function createManyClothes(array $clothes, bool $fromCollection = false, ?Collections $collection, array $selectedImage = []) :array
    {

        $arrayclothe = [] ;
        $index = 0;

        foreach($clothes[0] as $clothe){
            $arrayclothe = array_merge($arrayclothe, $this->createClothe($clothe, $fromCollection, $collection, $selectedImage[$index++]));
        };
        return $arrayclothe;
    }

    /**
     *  Creates one Clothes entity for each provided size.
     *
     * @param ClothesDTO $dto
     * @param string[] $sizes
     * @param Collections|null $collection
     * @param int|null $selectedImageIndex
     * @return clothes[]
     */
    private function createClothesForEachSize(ClothesDTO $dto, array $sizes, ?Collections $collection, ?int $selectedImageIndex): array
    {

        $name = $dto->getName();
        $color = $dto->getColor();

        $images = $this->getImage($dto->getImages(), $name, $selectedImageIndex ); //todo get images path in Json with clothImage DTO

        /** @var Clothes[] $clothes  */
        $clothes = array_map(function(string $size) use ($dto, $collection, $images,$name,$color){


            /** @var Clothescolor $clotheColor */
            $clotheColor =  $this->getClotheColor($color);
            $sku = (string) $this->getSku($name,$color,$size);
            $slug = (string) $this->slugger->slug($dto->getName());

            /** @var Clothessize $clotheSize */
            $clotheSize =  $this->getClotheSize($size);
  
            

            $clothe = new Clothes();
            $clothe 
                ->setName($name)
                ->setDescription($dto->getDescription())
                ->setMetaDescription($dto->getMetaDescription())
                ->setPrice($dto->getPrice())
                ->setStock($dto->getStock())
                ->setImages($images)
                ->setColor($clotheColor)
                ->setSize($clotheSize)
                ->setIsOnline(false)
                ->setSku($sku)
                ->setSlug($slug)
                ->setStatus('draft')
                ->setCollection($collection)
                ->setCreatedAt($this->today)
                ->setEditedAt($this->today)
            ;
            return $clothe;

        },$sizes);

        return $clothes;
    }

    private function getImage(array $imagesArray, string $name, ?int $selectedImageIndex ): array
    {
        // $index = 0;
        $images = array_map( function ($image, $index) use ($name ) {
            
            $imagePath = [
                'path'  => $this->getImagePath($image,$name),
                'index' => $index
            ];
            return $imagePath;
        },$imagesArray,range(0, count($imagesArray) - 1));

        $images = [$images[$selectedImageIndex], ...array_filter($images,function ($image) use ($selectedImageIndex) {
            return $selectedImageIndex !== $image['index'];
        })];

        return $images;
    }

    /**
     * Get clothes's image path
     *
     * @param UploadedFile $image
     * @param string $collectionName
     * @return string
     */
    private function getImagePath(UploadedFile $image,string $clotheName): string
    {

        $imageName = $this->fileUploader->upload($image, $this->getImageTargetDirectory(),$this->slugger->slug($clotheName));
        $imagePath = explode('public/',$this->getImageTargetDirectory())[1]."/".$this->slugger->slug($clotheName)."/".$imageName;

        return $imagePath;
    }

    /**
     * Get clothes's image's directory path
     *
     * @return string
     */
    private function getImageTargetDirectory():string
    {
        return  $this->targetDirectory ;
    }

    private function getSku(string $name, string $color, string $size): string
    {
        $namePart = $this->skuNormalizer($name);
        $colorPart = $this->skuNormalizer($color);
        $sizePart = $this->skuNormalizer($size);

        $sku = "SKU--".$namePart."--".$colorPart."--".$sizePart;
        return $sku;
    }

    private function skuNormalizer(string $part){

        $skuPart = mb_strtolower($part, 'UTF-8');
        $skuPart = iconv('UTF-8', 'ASCII//TRANSLIT', $skuPart);
        $skuPart = preg_replace('/[^a-z0-9]+/', '-', $skuPart);
        $skuPart = preg_replace('/-+/', '-', trim($skuPart, '-'));
        

        return mb_strtoupper($skuPart, 'UTF-8');
    }

    private function getClotheColor(string $colorName):Clothescolor
    {
        return $this->clothecolorFactory->createOrGet($colorName);
    }

    private function getClotheSize(string $sizeName){
        return $this->clothesizeFactory->createOrGet($sizeName);
    }




}
