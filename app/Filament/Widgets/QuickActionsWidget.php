<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions-widget';
    
    protected int | string | array $columnSpan = 'full'; 
    
    // Change this to a high negative number to force it to the top!
    protected static ?int $sort = -10; 
}