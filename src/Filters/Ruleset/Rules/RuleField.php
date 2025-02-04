<?php

namespace GregPriday\CopyTree\Filters\Ruleset\Rules;

enum RuleField: string
{
    case FOLDER = 'folder';
    case PATH = 'path';
    case DIRNAME = 'dirname';
    case BASENAME = 'basename';
    case EXTENSION = 'extension';
    case FILENAME = 'filename';
    case CONTENTS = 'contents';
    case CONTENTS_SLICE = 'contents_slice';
    case SIZE = 'size';
    case MTIME = 'mtime';
    case MIME_TYPE = 'mimeType';
}
