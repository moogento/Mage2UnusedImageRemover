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

class NonImageList extends Command
{

    const ALLOWED_FILE_TYPES = ['jpg','jpeg','png','gif','webp','svg'];

    protected $io;
    protected $file;
    protected $directoryList;
    protected $resourceConnection;
    protected $imagesPath;
    protected $showNotImageType;
    protected $db;

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

        $this->imagesPath = $this->getDir();

        $output->writeln("Checking files in directory: ".$this->imagesPath);
        $localImages = $this->getNonImagesFromDirectoryRecursive($this->imagesPath, $output);
        $output->writeln("Found ".count($localImages)." non-image files");
    }

    private function getNonImagesFromDirectoryRecursive($directory, $output, &$results = []) {
        if ($directoryContents = $this->file->readDirectory($directory)) {
            foreach ($directoryContents as $key => $path) {
                if(!is_dir($path)){
                    $match=false;
                    
                    if (!$this->endsWith(strtolower($path),'png')
                        && !$this->endsWith(strtolower($path),'gif')
                        && !$this->endsWith(strtolower($path),'svg')
                        && !$this->endsWith(strtolower($path),'webp')
                        && !$this->endsWith(strtolower($path),'jpg')
                        && !$this->endsWith(strtolower($path),'jpeg')) {
                        $output->writeln($path);
                    }

                    if(!$match) unset($directoryContents[$key]);
                } else if($path != "." && $path != ".." ){
                    $this->getNonImagesFromDirectoryRecursive($path, $output, $results);
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("ekouk:getnonimage");
        $this->setDescription("List non-image files from pub/media/");
        parent::configure();
    }
}
