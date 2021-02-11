<?php

namespace Sunnysideup\DeleteMembers\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataList;
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

    /**
     *
     * @var string
     */
    private static $always_exclude = '';

    /**
     *
     * @var string
     */
    protected $exclude = '';

    public function run($request)
    {
        if (Director::is_cli()) {
            $args = $request->getVar('args');
            $type = $args[0] ?? '';
            $this->exclude = $args[1] ?? '';
            if (! $type) {
                echo 'Please choose "test" or "delete" or "anonymise" as options';
            }
        } else {
            $type = $request->postVar('type');
            $this->exclude = $request->postVar('exclude');
            if (! $type) {
                echo $this->getForm();
            }
        }
        if ($type) {
            DB::alteration_message('Excluded phrases: "' . implode(', ', $this->excludeArray()) . '"' );
            foreach($this->excludedMembers() as $member) {
                DB::alteration_message('Excluding: ' . $member->Email);
            }
            switch ($type) {
                case 'test':
                    foreach ($this->getMembers() as $member) {
                        DB::alteration_message('To be deleted / anonymised ' . $member->Email, 'deleted');
                    }
                    break;
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

    public function getMembers() : DataList
    {
        $list = Member::get();
        $me = Security::getCurrentUser();
        if ($me && $me->ID) {
            $list = $list->excludeAny(
                [
                    'ID' => $me->ID,
                    'Email:PartialMatch' => $this->excludeArray()
                ]
            );
        }
        return $list;
    }

    public function excludedMembers() : DataList
    {
        $list = $this->getMembers();
        return Member::get()->subtract($list);
    }

    protected function excludeArray() : array
    {
        return array_filter(
            [
                trim($this->Config()->get('always_exclude')),
                $this->exclude,
            ]
        );

    }

    protected function getForm()
    {
        return '
<h1>Number of Members in Database: ' . Member::get()->count() . '</h1>
<form method="post" action="/dev/tasks/' . $this->Config()->get('segment') . '" >
    <br />
    <br />
    <label for="type">Action</label>
    <br />
    <select name="type">
        <option value=""></option>
        <option value="delete">delete</option>
        <option value="anonymise">anonymise</option>
    </select>
    <br />
    <br />
    <label for="name">exclude email snippets (exclude phrases (e.g. @mysite.co.nz), separated by comma)</label>
    <br />
    <input name="exclude" type="text" />
    <br />
    <br />
    <input name="go" type="submit" />
</form>
        ';
    }
}
