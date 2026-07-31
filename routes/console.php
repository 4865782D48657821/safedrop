<?php

use Illuminate\Foundation\Console\AboutCommand;

AboutCommand::add('Safedrop', fn () => [
    'MVP scope' => 'Discovery, project publishing metadata, safe external redirects',
    'File hosting' => 'Out of scope for MVP',
]);
