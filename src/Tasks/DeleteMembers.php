<?php

namespace Sunnysideup\DeleteMembers\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * deletes or anonymises all members
 */
class DeleteMembers extends BuildTask
{
    protected $title = 'Deletes all members';

    protected $description = 'Literally deletes or anonymises all members';

    private static $segment = 'careful-delete-members';

    public function run($request)
    {
        if (Director::is_cli()) {
            $args = $request->getVar('args');
            $type = $args[0] ?? '';
            if (! $type) {
                echo 'Please choose "delete" or "anonymise" as options';
            }
        } else {
            $type = $request->postVar('type');
            if (! $type) {
                echo $this->getForm();
            }
        }
        if ($type) {
            switch ($type) {
                case 'delete':
                    foreach ($this->getMembers() as $member) {
                        DB::alteration_message('DELETING ' . $member->Email, 'deleted');
                        $member->delete();
                    }
                    break;
                case 'anonymise':
                    foreach ($this->getMembers() as $member) {
                        DB::alteration_message('ANONYMISING ' . $member->Email, 'deleted');
                        $member->Surname = rand(0, 99999999999);
                        $member->FirstName = rand(0, 99999999999);
                        $member->Email = rand(0, 99999999999) . '@fake-address-nice-try.co.nz';
                        $member->write();
                    }
                    break;
                default:
                    user_error('Wrong type of action supplied: ' . $type);
            }
            DB::alteration_message('--- DONE ---');
        }
    }

    public function getMembers()
    {
        $list = Member::get();
        $me = Security::getCurrentUser();
        if ($me && $me->ID) {
            $list = $list->exclude(['ID' => $me->ID]);
        }
        return $list;
    }

    protected function getForm()
    {
        return '
        <h1>Number of Members in Database: ' . Member::get()->count() . '</h1>
<form method="post" action="/dev/tasks/' . Config ::inst()->get(self::class, 'segment') . '" >
    <select name="type">
        <option value=""></option>
        <option value="delete">delete</option>
        <option value="anonymise">anonymise</option>
    </select>
    <input name="go" type="submit" />
</form>
        ';
    }
}
