<?php

namespace Zeutzheim\PpintBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Zeutzheim\PpintBundle\Util\Utils;

class TestCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('ppint:test');
        /*
		$this->setDefinition(array(
			new InputArgument('platform', InputArgument::OPTIONAL, 'The platform the package is part of'),
			new InputArgument('package', InputArgument::OPTIONAL, 'The package which should be scanned'),
			new InputArgument('version', InputArgument::OPTIONAL, 'The version which should be scanned'),
		));
		$this->addOption('maxtime', 't', InputOption::VALUE_OPTIONAL, 'The maximum execution time', 60);
		$this->addOption('cachesize', 'c', InputOption::VALUE_NONE, 'Print the cachesize on start');
		$this->addOption('redownloadmaster', 'r', InputOption::VALUE_NONE, 'Redownload master-versions');
		$this->setDescription("Analyze one or more packages and add them to the index.\n
The command can index all packages, all packages of a platform or a single package, depending on the arguments specified.\n
If the version is not specified, the command tries to pick the newest version.");
        */
	}
    
    public function index($stmts, &$index) {
        foreach ($stmts as $idx => $stmt) {
            switch (variable) {
                case 'value':
                    
                    break;
                
                default:
                    
                    break;
            }
        }
    }

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

        $src = file_get_contents('test-parse.php');

        ini_set('xdebug.max_nesting_level', 2000);
        $parser = new \PhpParser\Parser(new \PhpParser\Lexer());
        try {
            $stmts = $parser->parse($src);

            $traverser = new \PhpParser\NodeTraverser();
            $indexer = new IndexNodeVisitor();
            $traverser->addVisitor(new \PhpParser\NodeVisitor\NameResolver());
            $traverser->addVisitor($indexer);
            $traverser->traverse($stmts);
            
            print_r($indexer->index);
            echo "\n\n\n";
            //print_r($stmts);
            exit;
        } catch (PhpParser\Error $e) {
            echo 'Parse Error: ', $e->getMessage();
            exit;
        }
	}

}

class IndexNodeVisitor extends \PhpParser\NodeVisitorAbstract
{
    
    public $index;
    
    public function __construct() {
        $this->index = array(
            'tag' => array(),
            'provide_class' => array(),
            'provide_namespace' => array(),
            'use_class' => array(),
            'use_namespace' => array(),
        );
    }
    
    public function enterNode(\PhpParser\Node $node) {
        // echo get_class($node) . "\n";
        // if ($node instanceof \PhpParser\Node\Name\FullyQualified) { $this->index['fq'][] = $node->toString(); }
        
        // Analyze provide
        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            Utils::array_add($this->index['provide_class'], $node->namespacedName->toString());
        }
        if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
            Utils::array_add($this->index['provide_namespace'], $node->name->toString());
        }
        
        // Analyze usage
        if ($node instanceof \PhpParser\Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                Utils::array_add($this->index['use_namespace'], $use->name->toString());
            }
        }
        if ($node instanceof \PhpParser\Node\Expr\New_) {
            Utils::array_add($this->index['use_class'], $node->class->toString());
        }
        if ($node instanceof \PhpParser\Node\Param) {
            Utils::array_add($this->index['use_class'], $node->type->toString());
        }
    }
}
