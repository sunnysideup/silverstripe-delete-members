# Upgrade to SilverStripe 6

## Requirements

- Minimum framework version updated from `^4.0 || ^5.0` to `^6.0`

## Task Execution Changes

⚠️ **Breaking Change**: The `DeleteMembers` task has been migrated from the legacy BuildTask format to the new Symfony Console command format.

### API Changes

**Execute Method**
- Replace `run($request)` method with `execute(InputInterface $input, PolyOutput $output): int`
- Must return `Command::SUCCESS` or `Command::FAILURE` status codes

**Input Handling**
- Replace `$request->getVar('args')` and `$request->postVar()` with `$input->getOption()`
- Add `getOptions()` method returning array of `InputOption` objects to define command options

**Output**
- Replace `DB::alteration_message()` calls with `$output->writeln()`
- Use Symfony Console output formatting: `<error>`, `<comment>`, `<info>` tags

**Configuration**
- Replace `private static $segment` with `protected static string $commandName`
- Change `protected $description` to `protected static string $description`
- Add type hints to properties: `protected string $title`

### Removed Features

⚠️ **Breaking Change**: Web-based form interface removed
- The `getForm()` method has been completely removed
- Tasks can only be executed via CLI using Symfony Console commands

### New Dependencies

Add the following use statements:
```php
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
```

Remove:
```php
use SilverStripe\Control\Director;
use SilverStripe\ORM\DB;
```

### Migration Example

**Before:**
```bash
php vendor/bin/sake dev/tasks/careful-delete-members type=test exclude="admin@"
```

**After:**
```bash
php vendor/bin/sake careful-delete-members --type=test --exclude="admin@"
```

🔍 **Note**: Verify all task invocations in deployment scripts, cron jobs, and documentation have been updated to use the new command format.
