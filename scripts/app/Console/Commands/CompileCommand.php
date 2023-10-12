<?php

declare(strict_types=1);
namespace App\Console\Commands;

use App\Helpers\FileHelper;
use App\Helpers\ImageResize;
use App\ResourceManager;

class CompileCommand extends AbstractCommand
{
    private string $sourceDir;
    private string $docsDir;

    public function __construct(ResourceManager $resourceManager)
    {
        parent::__construct($resourceManager);
        $this->sourceDir = $this->resourceManager->getConfig()->get('app.sourceDir');
        $this->docsDir = $this->resourceManager->getConfig()->get('app.docsDir');
    }

    /**
     * @return int|null
     * @throws \Exception
     */
    public function __invoke(): ?int
    {
        $this->scanSourceDir('');
        return 0;
    }

    /**
     * @param string $relPath
     * @return void
     * @throws \Exception
     */
    private function scanSourceDir(string $relPath): void
    {
        $dir = new \DirectoryIterator($this->sourceDir . $relPath);
        foreach ($dir as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            if ($entry->isDir()) {
                echo '[DIR] ' . $relPath . "/{$entry->getFilename()}\n";
                // echo "Going deeper\n";
                $this->scanSourceDir($relPath . '/' . $entry->getFilename());
                // echo "Returning back\n";
            } elseif ($entry->isFile()) {
                if (preg_match('/\.phtml$/', $entry->getFilename())) {
                    echo $relPath . "/{$entry->getFilename()}\n";
                    $phtmlFile = $this->sourceDir . $relPath . '/' . $entry->getFilename();
                    $sourceName = ltrim(preg_replace('/\.phtml$/', '', $relPath . '/' . $entry->getFilename()), '/');
                    $htmlFile = $this->docsDir . '/' . $sourceName . '.html';
                    $this->compileHtml($sourceName);
                    FileHelper::touch($htmlFile, FileHelper::filemtime($phtmlFile));

//                    $sourceDir = $this->sourceDir . '/' . $sourceName;
//                    var_dump($sourceDir);die;

                } elseif ($entry->getFilename() === 'gallery.php') {
                    echo $relPath . "/{$entry->getFilename()}\n";
                    $this->processGallery($relPath);
                }

            } else {
                throw new \RuntimeException("Directory entry \"{$entry->getPathname()}\" is neither a file nor a directory");
            }
        }
    }

    /**
     * @param string $sourceName    e.g. "food/millco"
     * @return void
     * @throws \Exception
     */
    private function compileHtml(string $sourceName): void
    {
        if (!is_dir($this->docsDir . '/' . dirname($sourceName))) {
            FileHelper::mkdir($this->docsDir . '/' . dirname($sourceName), 0777, true);
        }
        $html = $this->resourceManager->getTemplateEngine()->render('page', [
            'source' => $sourceName,
        ]);
        $htmlFile = $this->docsDir . '/' . $sourceName . '.html';
        FileHelper::file_put_contents($htmlFile, $html);
    }

    /**
     * @param string $relPath
     * @return void
     * @throws \Exception
     */
    private function processGallery(string $relPath): void
    {
        $sourceGalleryDir = $this->sourceDir . $relPath;
        $docsGalleryDir = $this->docsDir . $relPath;
        $galleries = require($sourceGalleryDir . '/gallery.php');

        foreach ($galleries as $galleryName => $photos) {
            if ($galleryName == 'preview') {
                continue;
            }
            foreach ($photos as $photo) {
                $photoRelPathFileName = is_array($photo) ? $photo[0] : $photo;
                $sourcePhotoFullPathFileName = $sourceGalleryDir . '/' . $photoRelPathFileName;
                $docsPhotoFullPathFileName = $docsGalleryDir . '/' . $photoRelPathFileName;
                if (!file_exists($sourcePhotoFullPathFileName)) {
                    throw new \Exception("File {$sourcePhotoFullPathFileName} not found");
                }
                echo $relPath . '/' . $photoRelPathFileName . " (" . filesize($sourcePhotoFullPathFileName) . ") - ";
                if (!is_dir($docsGalleryDir)) {
                    FileHelper::mkdir($docsGalleryDir, 0777, true);
                }
                if ($this->doNeedPhotoResample($sourcePhotoFullPathFileName)) {
                    if (!file_exists($docsPhotoFullPathFileName) || filemtime($docsPhotoFullPathFileName) != filemtime($sourcePhotoFullPathFileName)) {
                        echo "resample\n";
                        $image = ImageResize::resizeImage(
                            ImageResize::getImageFromFile($sourcePhotoFullPathFileName),
                            2000,
                            1500,
                            false,
                            false
                        );
                        imagejpeg($image, $docsPhotoFullPathFileName, 80);
                        FileHelper::touch($docsPhotoFullPathFileName, FileHelper::filemtime($sourcePhotoFullPathFileName));
                    } else {
                        echo "no need to resample\n";
                    }
                } else {
                    if (!file_exists($docsPhotoFullPathFileName) || filesize($docsPhotoFullPathFileName) != filesize($sourcePhotoFullPathFileName) || filemtime($docsPhotoFullPathFileName) != filemtime($sourcePhotoFullPathFileName)) {
                        echo "copy\n";
                        $docsPhotoFullPath = dirname($docsPhotoFullPathFileName);
                        if (!is_dir($docsPhotoFullPath)) {
                            FileHelper::mkdir($docsPhotoFullPath, 0777, true);
                        }
                        FileHelper::copy($sourcePhotoFullPathFileName, $docsPhotoFullPathFileName);
                    } else {
                        echo "no need to copy\n";
                    }
                }
            }
        }

        $sourcePreviewFile = !empty($galleries['preview']) ? $galleries['preview'] : reset($galleries)[0];
        $docsPreviewFile = '/preview.jpg';
        echo "Preview " . $relPath . '/' . $sourcePreviewFile . ' - ';
        if (!file_exists($docsGalleryDir . $docsPreviewFile) || filemtime($sourceGalleryDir . '/' . $sourcePreviewFile) != filemtime($docsGalleryDir . $docsPreviewFile)) {
            echo "create\n";
            $this->createPreview($sourceGalleryDir . '/' . $sourcePreviewFile, $docsGalleryDir . $docsPreviewFile);
        } else {
            echo "already exists\n";
        }
    }

    private function doNeedPhotoResample(string $photoFullPathFileName): bool
    {
        return filesize($photoFullPathFileName) > 900 * 1024;
    }

    private function createPreview(string $sourceImage, string $previewImage): void
    {
        $image = ImageResize::resizeImage(
            ImageResize::getImageFromFile($sourceImage),
            365,
            182,
            true,
            true
        );
        imagejpeg($image, $previewImage, 90);
        FileHelper::touch($previewImage, FileHelper::filemtime($sourceImage));
    }
}
