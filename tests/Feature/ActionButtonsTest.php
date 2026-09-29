<?php

namespace Tests\Feature;

use App\Support\ActionButtons;
use Tests\TestCase;

class ActionButtonsTest extends TestCase
{
    public function test_renders_only_requested_buttons(): void
    {
        $html = ActionButtons::make(urlEdit: 'http://app.test/desa/edit/5');

        $this->assertStringContainsString('data-src="http://app.test/desa/edit/5"', $html);
        $this->assertStringNotContainsString('btn-action-view', $html);
        $this->assertStringNotContainsString('btn-delete', $html);
    }

    public function test_delete_button_uses_global_handler_and_escapes_values(): void
    {
        $html = ActionButtons::make(urlDelete: 'http://app.test/desa/destroy', idField: 'id_desa', idValue: '"><script>x</script>');

        $this->assertStringContainsString('class="btn-delete btn-action-delete"', $html);
        $this->assertStringContainsString('data-id-field="id_desa"', $html);
        $this->assertStringNotContainsString('<script>x</script>', $html);
    }

    public function test_delete_button_hidden_without_id(): void
    {
        $this->assertStringNotContainsString('btn-delete', ActionButtons::make(urlDelete: 'http://app.test/desa/destroy'));
    }
}
