<?php

declare(strict_types=1);

namespace SlimEdge\Commands;

use SebastianBergmann\Environment\Console;
use Slim\Interfaces\RouteCollectorInterface;
use SlimEdge\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RouteList extends Command
{
    /**
     * @var RouteCollectorInterface $router
     */
    private $router;

    public function  __construct()
    {
        $this->router = Kernel::$app->getRouteCollector();

        $this->setName('route-list');
        $this->setDescription('List available routes');
        
        parent::__construct();
    }

    public function configure()
    {
        $this->addOption('search', 's', InputOption::VALUE_REQUIRED, 'Search');
        $this->addOption('search-by', 'b',
            InputOption::VALUE_REQUIRED,
            'Search by (method, uri, name, action)'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ConsoleOutput $output */
        $table = new Table($output->section());
        
        $table->setStyle('box-double');
        $table->setHeaders(['Method', 'URI Pattern', 'Name', 'Action']);

        $searchQuery = $input->getOption('search');

        $searchBy = $input->getOption('search-by');
        if($searchBy) {
            $searchBy = explode(',', $searchBy);
            $searchBy = array_map('strtolower', $searchBy);
            $searchBy = array_map('trim', $searchBy);
            $searchBy = array_unique($searchBy);
        }
        else $searchBy = ['method', 'uri', 'name', 'action'];
        
        $actionResolver = function($item) {
            if(is_array($item)) {
                return $item[0] . '<fg=gray>:' . $item[1] . '</>';
            }
            elseif(is_object($item)) {
                if(method_exists($item, '__toString')) {
                    return (string) $item;
                }
                return get_class($item);
            }
            return $item;
        };

        $methodResolver = function($item) {
            $item = array_map(function($item) {
                switch($item) {
                    case 'GET':
                    $item = '<fg=green>' . $item;
                    break;

                    case 'POST':
                    case 'PATCH':
                    case 'PUT':
                    $item = '<fg=yellow>' . $item;
                    break;

                    case 'DELETE':
                    case 'PURGE':
                    $item = '<fg=red>' . $item;
                    break;

                    case 'OPTIONS':
                    $item = '<fg=cyan>' . $item;
                    break;

                    default: return $item;
                }
                
                return $item . '</>';
            }, $item);

            return join(',', $item);
        };

        foreach($this->router->getRoutes() as $route) {
            $methods = $methodResolver($route->getMethods());
            $pattern = $route->getPattern();
            $name = $route->getName();
            $action = $actionResolver($route->getCallable());

            if(!empty($searchQuery)) {
                $foundOne = false;

                $found = stripos($methods, $searchQuery) !== false;
                if(!empty($searchBy) && in_array('method', $searchBy))
                    $foundOne = $foundOne || $found;

                $found = stripos($pattern, $searchQuery) !== false;
                if(!empty($searchBy) && in_array('uri', $searchBy))
                    $foundOne = $foundOne || $found;

                $found = stripos($name, $searchQuery) !== false;
                if(!empty($searchBy) && in_array('name', $searchBy))
                    $foundOne = $foundOne || $found;

                $found = stripos($action, $searchQuery) !== false;
                if(!empty($searchBy) && in_array('action', $searchBy))
                    $foundOne = $foundOne || $found;

                if(!$foundOne) continue;
            }

            $table->appendRow([$methods, $pattern, $name, $action]);
        }

        return Command::SUCCESS;
    }
}