<?php namespace Mitch\LaravelDoctrine\Console\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class ValidateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'doctrine:schema:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate the mapping files.';

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $em = $this->entityManager;
        $validator = new SchemaValidator($em);
        $exit = 0;

        if ($this->option('skip-mapping')) {
            $this->comment('[Mapping]  Skipped mapping check.');
        } elseif ($errors = $validator->validateMapping()) {
            foreach ($errors as $className => $errorMessages) {
                $this->error("[Mapping]  FAIL - The entity-class '" . $className . "' mapping is invalid:");

                foreach ($errorMessages as $errorMessage) {
                    $this->line('* ' . $errorMessage);
                }

                $this->line('');
            }

            $exit += 1;
        } else {
            $this->info('[Mapping]  OK - The mapping files are correct.');
        }

        if ($this->option('skip-sync')) {
            $this->comment('[Database] SKIPPED - The database was not checked for synchronicity.');
        } elseif (!$validator->schemaInSyncWithMetadata()) {
            $this->error('[Database] FAIL - The database schema is not in sync with the current mapping file.');
            $exit += 2;
        } else {
            $this->info('[Database] OK - The database schema is in sync with the mapping files.');
        }

        return $exit;
    }

    protected function getOptions()
    {
        return [
            ['skip-mapping', null, InputOption::VALUE_NONE, 'Skip the mapping validation check.'],
            ['skip-sync', null, InputOption::VALUE_NONE, 'Skip checking if the mapping is in sync with the database.']
        ];
    }
}

