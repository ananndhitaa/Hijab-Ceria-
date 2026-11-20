<?php

namespace App\View\Components;

use App\Support\StatusBadgePresenter;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public function __construct(
        public mixed $status = null,
        public ?string $context = null,
        public ?string $label = null,
        public string $size = 'table',
        public bool $customer = false
    ) {}

    public function render(): View
    {
        return view('components.status-badge');
    }
}
