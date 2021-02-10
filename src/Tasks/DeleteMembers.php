<?php

namespace Sunnysideup\DeleteMembers\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\Connect\TempDatabase;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Cleans up leftover databases from aborted test executions (starting with ss_tmpdb)
 * Task is restricted to users with administrator rights or running through CLI.
 */
class DeleteMembers extends BuildTask
{

    private static $segment = 'careful-delete-members';

    protected $title = 'Deletes all members';

    protected $description = 'Literally deletes or anonymises all members';

    public function run($request)
    {
        switch($this->postVar('type')) {
            case 'delete':
                foreach(Member::get() as $member) {
                    DB::alteration_message('DELETING '.$member->Email, 'deleted');
                    $member->delete;
                }
            default:
                echo $this->getForm();
        }

    }


    protected function getForm()
    {
        return '
<form method="post" action="dev/tasks/'.Config::inst()->get(self::class, 'segment').'" >
    <select name="type">
        <option value=""></option>
        <option value="delete">delete</option>
        <option value="anonymise">anonymise</option>
    </select>
</form>
<input name="go" type="submit" />
        ';


    }
}
