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

class DodgyImageClean extends Command
{

    const LIST_MODE = "List Mode";

    protected $io;
    protected $file;
    protected $directoryList;
    protected $imagesPath;
    protected $listMode;

    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Filesystem\Io\File $io,
        DirectoryList $directoryList
    ){
        $this->io = $io;
        $this->file = $file;
        $this->directoryList = $directoryList;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->listMode = $input->getOption(self::LIST_MODE);
        $this->imagesPath = $this->getDir();

        $output->writeln("Checking Files In Directory: ".$this->imagesPath);
        $localImages = $this->getAttachVectorPhp($this->imagesPath);
        $output->writeln("Found ".count($localImages)." dodgy image files ! Check the contents before deleting !");

        $deleteList = $this->createListToDelete($localImages);

        $output->writeln("Test Mode Only - Nothing deleted");
        if ($this->listMode) {
            $this->listDeleteList($deleteList);
        }
    }

    private function getAttachVectorPhp($directory) {
        $exec = exec('grep -l -R "\(eval\|base64_decode\|shell_exec\|error_reporting\(0\)\|gzinflate(base64_decode\|eval\|shell_exec\|error_reporting\(0\)(gzinflate(base64_decode\|eval(base64_decode\)" ' . $directory, $output);
        return $output;
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
        printf( "Found %d dodgy image files to be checked, using %d Mb\n", count( $deleteList ), $deleteSize );
        return $deleteList;
    }

    private function listDeleteList($deleteList){
        echo "Files marked for checking:\n";
        foreach( $deleteList as $deleteFile ) {
            echo "$deleteFile\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("ekouk:dodgycleanimages");
        $this->setDescription("List and remove corrupt images from pub/media");
        $this->setDefinition([
            new InputOption(self::LIST_MODE, "-l", InputOption::VALUE_NONE, "List Mode")
        ]);
        parent::configure();
    }
}
