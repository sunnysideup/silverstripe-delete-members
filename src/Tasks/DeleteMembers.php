<?php

namespace Sunnysideup\DeleteMembers\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * deletes or anonymises all members.
 */
class DeleteMembers extends BuildTask
{
    protected string $title = 'Deletes all members';

    protected static string $description = 'Literally deletes or anonymises all members';

    /**
     * @var string
     */
    protected $exclude = '';

    protected static string $commandName = 'careful-delete-members';

    /**
     * @var string
     */
    private static $always_exclude = '';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        // Get options from CLI input
        $type = $input->getOption('type');
        $this->exclude = $input->getOption('exclude') ?: '';

        if (!$type) {
            $output->writeln('<error>Please choose "test" or "delete" or "anonymise" as options</error>');
            return Command::FAILURE;
        }

        // Display excluded phrases
        $output->writeln('Excluded phrases: "' . implode(', ', $this->excludeArray()) . '"');
        foreach ($this->excludedMembers() as $member) {
            $output->writeln('Excluding: ' . $member->Email);
        }

        switch ($type) {
            case 'test':
                foreach ($this->getMembers() as $member) {
                    $output->writeln('<comment>To be deleted / anonymised ' . $member->Email . '</comment>');
                }
                break;

            case 'delete':
                foreach ($this->getMembers() as $member) {
                    $output->writeln('<error>DELETING ' . $member->Email . '</error>');
                    $member->delete();
                }
                break;

            case 'anonymise':
                foreach ($this->getMembers() as $member) {
                    $output->writeln('<comment>ANONYMISING ' . $member->Email . '</comment>');
                    $member->Surname = random_int(0, 99999999999);
                    $member->FirstName = random_int(0, 99999999999);
                    $member->Email = random_int(0, 99999999999) . '@fake-address-nice-try.co.nz';
                    $member->write();
                }
                break;

            default:
                $output->writeln('<error>Wrong type of action supplied: ' . $type . '</error>');
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('type', 't', InputOption::VALUE_REQUIRED, 'Action type: "test", "delete", or "anonymise"'),
            new InputOption('exclude', 'e', InputOption::VALUE_OPTIONAL, 'Exclude email snippets (e.g. "@mysite.co.nz" or "john.smith"), separated by comma'),
        ];
    }

    public function getMembers(): DataList
    {
        return Member::get()->subtract($this->excludedMembers());
    }

    public function excludedMembers(): DataList
    {
        $list = Member::get();
        $myId = 0;
        $me = Security::getCurrentUser();
        if ($me && $me->ID) {
            $myId = $me->ID;
        }

        return $list->filterAny(
            [
                'ID' => $myId,
                'Email:PartialMatch' => $this->excludeArray(),
            ]
        );
    }

    protected function excludeArray(): array
    {
        $string = $this->Config()->get('always_exclude') . ', ' . $this->exclude;
        $array = explode(',', $string);
        $array = array_map(trim(...), $array);

        return array_filter($array);
    }
}
