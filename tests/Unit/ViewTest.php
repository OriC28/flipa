<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\View\Exceptions\ViewNotFoundException;
use Core\View\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    private string $primaryViewDir;
    private string $secondaryViewDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->primaryViewDir = sys_get_temp_dir() . '/flipa_views_primary_' . uniqid();
        $this->secondaryViewDir = sys_get_temp_dir() . '/flipa_views_secondary_' . uniqid();

        mkdir($this->primaryViewDir, 0777, true);
        mkdir($this->secondaryViewDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->primaryViewDir);
        $this->deleteDirectory($this->secondaryViewDir);

        parent::tearDown();
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    public function test_renders_template_and_evaluates_variables_isolated_via_extract(): void
    {
        $templatePath = $this->primaryViewDir . '/welcome.php';
        file_put_contents($templatePath, '<h1>Hello <?= $name ?>!</h1><p>Age: <?= $age ?></p>');

        $view = new View($this->primaryViewDir);
        $rendered = $view->render('welcome', ['name' => 'Flipa', 'age' => 25]);

        $this->assertSame('<h1>Hello Flipa!</h1><p>Age: 25</p>', $rendered);
    }

    public function test_supports_dot_notation_and_converts_to_nested_path(): void
    {
        $usersDir = $this->primaryViewDir . '/users';
        mkdir($usersDir, 0777, true);
        file_put_contents($usersDir . '/profile.php', '<div>User: <?= $username ?></div>');

        $reportsDir = $this->primaryViewDir . '/admin/reports';
        mkdir($reportsDir, 0777, true);
        file_put_contents($reportsDir . '/weekly.php', '<span>Report: <?= $period ?></span>');

        $view = new View($this->primaryViewDir);

        $this->assertSame('<div>User: john_doe</div>', $view->render('users.profile', ['username' => 'john_doe']));
        $this->assertSame('<span>Report: 2026-W45</span>', $view->render('admin.reports.weekly', ['period' => '2026-W45']));
    }

    public function test_cascade_search_supports_multiple_base_paths_and_respects_precedence(): void
    {
        file_put_contents($this->secondaryViewDir . '/fallback.php', 'Secondary fallback content');

        file_put_contents($this->primaryViewDir . '/shared.php', 'Primary shared content');
        file_put_contents($this->secondaryViewDir . '/shared.php', 'Secondary shared content');

        $view = new View([$this->primaryViewDir, $this->secondaryViewDir]);

        $this->assertSame('Secondary fallback content', $view->render('fallback'));
        $this->assertSame('Primary shared content', $view->render('shared'));
    }

    public function test_throws_view_not_found_exception_when_template_does_not_exist_in_single_path(): void
    {
        $view = new View($this->primaryViewDir);

        $this->expectException(ViewNotFoundException::class);
        $view->render('non_existent');
    }

    public function test_throws_view_not_found_exception_when_template_does_not_exist_in_multiple_paths(): void
    {
        $view = new View([$this->primaryViewDir, $this->secondaryViewDir]);

        $this->expectException(ViewNotFoundException::class);
        $view->render('missing.nested.view');
    }

    public function test_clean_output_buffer_management_prevents_output_leak_on_exception(): void
    {
        $templatePath = $this->primaryViewDir . '/faulty.php';
        file_put_contents($templatePath, 'Buffer before error<?php throw new \RuntimeException("Crash inside template"); ?>Buffer after error');

        $view = new View($this->primaryViewDir);
        $initialLevel = ob_get_level();

        ob_start();
        try {
            $view->render('faulty');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Crash inside template', $e->getMessage());
        } finally {
            $leakedOutput = ob_get_clean();
        }

        $this->assertSame($initialLevel, ob_get_level());
        $this->assertSame('', $leakedOutput);
    }
}
