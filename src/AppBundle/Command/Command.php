<?php
namespace AppBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Website;

abstract class Command extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var EntityRepository
     */
    protected $repo;

    protected function configure() {
        $this->addOption('list-types', null, InputOption::VALUE_NONE, 'List all website types');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->em = $this->getContainer()->get('doctrine')->getEntityManager('default');
        $this->repo = $this->em->getRepository('AppBundle:Website');

        if ((bool)$this->input->getOption('list-types')) {
            $types = [];
            foreach ($this->getWebsites() as $website) {
                $type = $website->getType();
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                }
                $types[$type]++;
            }
            ksort($types);
            foreach ($types as $type => $cardinality) {
                $this->output->writeln($type . ': ' . $cardinality);
            }
            exit;
        }

        $this->runCommand();
    }

    abstract protected function runCommand();

    protected function getWebsites(array $query = [])
    {
        return $this->repo->findBy($query);
    }

    protected function getWebsitesByTypes(array $types) {
        return $this->repo->findByTypes($types);
    }

    protected function getWebsite(array $query = [])
    {
        $result = $this->repo->findBy($query);
        return (count($result) > 0) ? $result[0] : null;
    }

    protected function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    protected function writeln()
    {
        $args = func_get_args();
        call_user_func_array([ $this->output, 'writeln' ], $args);
    }
}
