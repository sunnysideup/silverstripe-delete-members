<?php

namespace Sunnysideup\DeleteMembers\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

/**
 * deletes or anonymises all members.
 */
class DeleteMembers extends BuildTask
{
    protected $title = 'Deletes all members';

    protected $description = 'Literally deletes or anonymises all members';

    /**
     * @var string
     */
    protected $exclude = '';

    private static $segment = 'careful-delete-members';

    /**
     * @var string
     */
    private static $always_exclude = '';

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
            // deleting or anonymising can only be done with the form on this page (so not from a link on another website).
            if ($type && ! SecurityToken::inst()->checkRequest($request)) {
                DB::alteration_message('Sorry, the security token is missing or has expired. Please use the form.', 'error');
                $type = '';
            }

            if (! $type) {
                echo $this->getForm();
            }
        }

        if ($type && 'test' !== $type && Director::isLive()) {
            DB::alteration_message('Members can not be deleted or anonymised on a live site.', 'error');

            return;
        }

        if ($type) {
            DB::alteration_message('Excluded phrases: "' . implode(', ', $this->excludeArray()) . '"');
            foreach ($this->excludedMembers() as $member) {
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
                        $member->Surname = random_int(0, 99999999999);
                        $member->FirstName = random_int(0, 99999999999);
                        $member->Email = random_int(0, 99999999999) . '@fake-address-nice-try.co.nz';
                        $member->write();
                    }

                    break;
                default:
                    user_error('Wrong type of action supplied: ' . $type);
            }

            DB::alteration_message('--- DONE ---');
        }
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

    protected function getForm()
    {
        return '
<h1>Number of Members in Database: ' . Member::get()->count() . '</h1>
<form method="post" action="/dev/tasks/' . Convert::raw2att($this->Config()->get('segment')) . '" >
    <input type="hidden" name="' . Convert::raw2att(SecurityToken::inst()->getName()) . '" value="' . Convert::raw2att(SecurityToken::inst()->getValue()) . '" />
    <br />
    <br />
    <label for="type">Action</label>
    <br />
    <select name="type">
        <option value="">--- please select type of action --- </option>
        <option value="test">test only</option>
        <option value="delete">delete !</option>
        <option value="anonymise">anonymise !</option>
    </select>
    <br />
    <br />
    <label for="name">exclude email snippets (e.g. "@mysite.co.nz" or "john.smith", separated by comma)</label>
    <br />
    <input name="exclude" type="text" value="' . Convert::raw2att((string) $this->exclude) . '" />
    <br />
    <br />
    <input name="go" type="submit" />
</form>
        ';
    }
}
