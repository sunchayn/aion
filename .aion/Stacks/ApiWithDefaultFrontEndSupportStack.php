<?php

namespace Aion\Stacks;

use Aion\Engine\Operations\AppendToFileOperation;
use Aion\Engine\Operations\CopyDirectoryOperation;
use Aion\Engine\Operations\DeleteFileOperation;
use Aion\Engine\Operations\OperationContract;
use Aion\Engine\PathResolver;

class ApiWithDefaultFrontEndSupportStack extends BareApiStack
{
    public function getName(): string
    {
        return 'API with Default Front End Stack';
    }

    public function getDescription(): string
    {
        return 'A stack that provides an API with default Frontend support.';
    }

    /**
     * @return OperationContract[]
     */
    public function getOperations(PathResolver $pathResolver): array
    {
        return [
            new CopyDirectoryOperation(source: $pathResolver->stub('with-fe'), destination: '.'),
            new DeleteFileOperation(filePath: 'config/view.php'),
            new AppendToFileOperation(
                filePath: 'app/Http/Web/routes/web.php',
                content: "Route::get('/', fn () => view('pages.welcome'));"
            ),
        ];
    }
}
