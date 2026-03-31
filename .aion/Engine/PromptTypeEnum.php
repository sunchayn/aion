<?php

namespace Aion\Engine;

enum PromptTypeEnum: string
{
    case Confirm = 'confirm';

    case Select = 'select';

    case MultiSelect = 'multiselect';
}
