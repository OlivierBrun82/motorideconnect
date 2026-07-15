<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    // Largeur maximale de sortie : les images plus larges sont réduites (ratio conservé)
    private const MAX_WIDTH = 1920;

    // Taille maximale du fichier de sortie (3 Mo). On baisse la qualité jusqu'à la respecter.
    private const MAX_BYTES = 3 * 1024 * 1024;

    public function __construct(
        // Dossier racine des uploads, injecté automatiquement
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private string $uploadsDir,
        private SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $file, string $subDirectory): string
    {
        // Nom d'origine sans l'extension, "slugifié" pour être sûr dans une URL
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($originalName);

        // On force .webp : toutes les images sont converties en WebP
        $newFilename = $safeName . '-' . uniqid() . '.webp';

        // Dossier de destination (ex. public/uploads/motorcycles)
        $destination = $this->uploadsDir . '/' . $subDirectory;

        // Traite l'image (resize + conversion WebP + suppression de l'EXIF)
        // et l'écrit directement à destination.
        $this->processImage($file->getPathname(), $destination . '/' . $newFilename);

        // On renvoie juste le nom : c'est lui qu'on stockera en base
        return $newFilename;
    }

    
     // Lit l'image source, la redimensionne si besoin, la ré-enregistre en WebP.
     // Le ré-encodage ne recopie AUCUNE métadonnée EXIF (vie privée).
     
    private function processImage(string $sourcePath, string $destinationPath): void
    {
        // 1. Lit les infos de l'image source (dimensions + type réel)
        $info = getimagesize($sourcePath);
        if ($info === false) {
            throw new FileException('Fichier image invalide.');
        }
        [$width, $height] = $info;
        $mime = $info['mime'];

        // 2. Crée une ressource GD selon le format d'entrée
        $source = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default      => throw new FileException("Format non supporté : $mime"),
        };

        // 3. Calcule les nouvelles dimensions (on réduit seulement si trop large)
        if ($width > self::MAX_WIDTH) {
            $newWidth  = self::MAX_WIDTH;
            $newHeight = (int) ($height * self::MAX_WIDTH / $width); // garde le ratio
        } else {
            $newWidth  = $width;
            $newHeight = $height;
        }

        // 4. Crée l'image de destination et préserve la transparence (PNG/WebP)
        $destination = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($destination, false);
        imagesavealpha($destination, true);

        // Recopie la source redimensionnée dans la destination
        imagecopyresampled(
            $destination, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight, $width, $height
        );

        // 5. Enregistre en WebP en baissant la qualité tant que le fichier dépasse 3 Mo
        //    (pas d'EXIF repris au passage). On s'arrête à une qualité plancher de 40.
        $quality = 80;
        do {
            imagewebp($destination, $destinationPath, $quality);
            clearstatcache(true, $destinationPath); // sinon filesize renvoie une valeur en cache
            $quality -= 10;
        } while (filesize($destinationPath) > self::MAX_BYTES && $quality >= 40);

        // 6. Libère la mémoire (GD garde tout en RAM)
        imagedestroy($source);
        imagedestroy($destination);
    }
}
