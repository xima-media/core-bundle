<?php
namespace Xima\CoreBundle\Command;

use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ExportEntitiesCommand extends ContainerAwareCommand
{
    const EXPORT_FOLDER = 'export';

    protected function configure()
    {
        $this
            ->setName('xima:export-entities')
            ->setDescription('A command to export all entities of a specified type to a json file. Can be done incrementally.')
            ->addArgument('entity', InputArgument::REQUIRED, 'The database you want to export')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'The database query limit user', '500')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = $input->getArgument('entity');
        $limit = $input->getOption('limit');

        if (!class_exists ($entity)) {
            $output->writeln('<error>Error!</error> Class \''.$entity.'\' not found.');
            return;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository($entity);
        /* @var $repository \Doctrine\ORM\EntityRepository */

        if (!is_a ($repository, '\Doctrine\ORM\EntityRepository')) {
            $output->writeln('<error>Error!</error> No EntityRepository found.');
            return;
        }
        // configuration and setup
        $fs = new Filesystem();
        $reflection = $em->getClassMetadata($entity)->getReflectionClass();
        /* @var $reflection \ReflectionClass */

        $serializer = SerializerBuilder::create()
            ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
            ->build();

        // export directory
        $dir = $this->getContainer()->get('kernel')->getRootDir().'/'.self::EXPORT_FOLDER.'/'.str_replace('\\', '/', $reflection->getName());
        if (!$fs->exists($dir)) {
            $fs->mkdir($dir);
        }

        $fileName = $reflection->getShortName().'.json';
        $file = $dir.'/'.$fileName;
        $tmpFile = $dir.'/_'.$fileName;

        // do the export only if it was not completed today
        if ($fs->exists($file) && date('F d Y', strtotime('today')) == date('F d Y', filemtime($file))) {
            $output->writeln('<comment>Good</comment> - the file was updated today...');
            return;
        }

        $offsetFile = $dir.'/last_offset';
        // the offset is 0 or the last one used + limit
        $offset = ($fs->exists($offsetFile)) ? file_get_contents($offsetFile) : 0;

        // now get the entities from db
        $result = $repository->findBy(array(), array(), $limit, $offset);
        $data = json_decode($serializer->serialize($result, 'json'), true);

        if ($fs->exists($tmpFile)) {
            $data = array_merge(json_decode(file_get_contents($tmpFile), true), $data);
        }
        $jsonData = json_encode($data);

        $count = count($result);
        $countTotal=$count+$offset;

        if ($count == $limit) {
            //still more in the db
            $fs->dumpFile($tmpFile, $jsonData);
            $fs->dumpFile($offsetFile, $countTotal);
            $output->writeln('<comment>Good</comment> - exported '.$count. ' entities of type \''.$reflection->getName().'\' - now having '.$countTotal.', waiting for more.');
        } else {
            //export completed
            $fs->dumpFile($file, $jsonData);
            $fs->remove($offsetFile);
            $fs->remove($tmpFile);

            $output->writeln('<info>Done</info> - exported all '.$count.' entities of type \''.$reflection->getName().'\'.');
        }
    }
}