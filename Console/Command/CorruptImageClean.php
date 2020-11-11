<?php
/**
 * Inspired by jeroenvermeulen Clean Images.
 *
 * End-User License Agreement (EULA) of EkoUK/ImageCleaner
 *
 * License Grant
 *
 * EKO UK LTD hereby grants you a personal, non-transferable, non-exclusive licence to use the EkoUK/ImageCleaner software on your devices in accordance with the terms of this EULA agreement.
 *
 * You are permitted to load the EkoUK/ImageCleaner software (for example a PC, laptop, mobile or tablet) under your control. You are responsible for ensuring your device meets the minimum requirements of the EkoUK/ImageCleaner software.
 *
 * You are not permitted to:
 *
 * - Edit, alter, modify, adapt, translate or otherwise change the whole or any part of the Software nor permit the whole or any part of the Software to be combined with or become incorporated in any other software, nor decompile, disassemble or reverse engineer the Software or attempt to do any such things
 * - Reproduce, copy, distribute, resell or otherwise use the Software for any commercial purpose
 * - Allow any third party to use the Software on behalf of or for the benefit of any third party
 * - Use the Software in any way which breaches any applicable local, national or international law
 * - Use the Software for any purpose that EKO UK LTD considers is a breach of this EULA agreement
 *
 * Full License may be found here: https://www.ekouk.com/software-end-user-licence-agreement/
 */

namespace EkoUK\ImageCleaner\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;

class CorruptImageClean extends Command
{

    const DELETE_MODE = "Delete Mode";
    const LIST_MODE = "List Mode";
    const ALLOWED_FILE_TYPES = ['jpg','jpeg','png','gif','webp'];

    protected $io;
    protected $file;
    protected $directoryList;
    protected $resourceConnection;
    protected $imagesPath;
    protected $deleteMode;
    protected $listMode;

    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Filesystem\Io\File $io,
        DirectoryList $directoryList,
        ResourceConnection $resourceConnection
    ){
        $this->io = $io;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->resourceConnection = $resourceConnection;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {

        $this->deleteMode = $input->getOption(self::DELETE_MODE);
        $this->listMode = $input->getOption(self::LIST_MODE);
        $this->imagesPath = $this->getDir();

        $output->writeln("Checking Files In Directory: ".$this->imagesPath);
        $localImages = $this->getCorruptedImagesFromDirectoryRecursive($this->imagesPath);
        $output->writeln("Found ".count($localImages)." corrupted image files");

        $deleteList = $this->createListToDelete($localImages);

        if($this->deleteMode){
            $output->writeln("Deleting Files");
            $this->deleteImages($deleteList);
            $output->writeln("All Done");

        } else {
            $output->writeln("Test Mode Only - Nothing deleted");
            if ($this->listMode) {
                $this->listDeleteList($deleteList);
            }
        }
    }

    private function getCorruptedImagesFromDirectoryRecursive($directory,&$results = []) {
        if ($directoryContents = $this->file->readDirectory($directory)) {
            foreach ($directoryContents as $key => $path) {
                if(!is_dir($path)){
                    $match=false;
                    foreach (self::ALLOWED_FILE_TYPES as $ext){
                        if($this->endsWith(strtolower($path),$ext)
                            && strpos($path, 'product/cache/') === false
                        ){
                            $fext = $ext;
                            if ($fext == 'jpg') {
                                $fext = 'jpeg';
                            }
                            $function = 'imagecreatefrom' . $fext;
                            if (function_exists($function) && @$function($path) === FALSE) {
                                $results[] = $path;
                            }
                        }
                    }

                    if(!$match) unset($directoryContents[$key]);
                } else if($path != "." && $path != ".." ){
                    $this->getCorruptedImagesFromDirectoryRecursive($path,$results);
                }
            }
        }
        return $results;
    }

    protected function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    protected function getDir()
    {
        return $this->directoryList->getPath('media').'/';
    }

    private function createListToDelete($localImages){

        $deleteList = array();
        $deleteSize = 0;
        foreach ($localImages as $file){
            if ( is_writable( $file ) ) {
                $deleteList[] = $file;
                $deleteSize += filesize( $file ) / 1024 / 1024; // Add in Mb
            } else {
                printf( "Warning: File '%s' is not writable, skipping.\n", $file );
            }
        }
        printf( "Found %d corrupt image files to be deleted, using %d Mb\n", count( $deleteList ), $deleteSize );
        return $deleteList;
    }

    private function deleteImages($deleteList){
        foreach( $deleteList as $deleteFile ) {
            unlink( $deleteFile );
        }
    }

    private function listDeleteList($deleteList){
        echo "Files marked for deletion:\n";
        foreach( $deleteList as $deleteFile ) {
            echo "$deleteFile\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("ekouk:corruptcleanimages");
        $this->setDescription("List and remove corrupt images from pub/media");
        $this->setDefinition([
            new InputOption(self::DELETE_MODE, "-d", InputOption::VALUE_NONE, "Delete Mode"),
            new InputOption(self::LIST_MODE, "-l", InputOption::VALUE_NONE, "List Mode")
        ]);
        parent::configure();
    }
}
