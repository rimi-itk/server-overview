<?php
namespace AppBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->em = $this->getContainer()->get('doctrine')->getEntityManager('default');
        $this->repo = $this->em->getRepository('AppBundle:Website');

        $this->runCommand();
    }

    abstract protected function runCommand();

    protected function getWebsites(array $query = [])
    {
        return $this->repo->findBy($query);
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
