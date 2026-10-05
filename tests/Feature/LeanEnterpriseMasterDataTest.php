<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Department;
use App\Models\Factory;
use App\Models\GsdCategory;
use App\Models\GsdElement;
use App\Models\Operator;
use App\Models\Process;
use App\Models\ProcessVersion;
use App\Models\ProductionLine;
use App\Models\PtmsReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeanEnterpriseMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function tinyPngUpload(string $name): UploadedFile
    {
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_authenticated_user_can_view_core_home_and_master_data_pages(): void
    {
        $role = Role::create([
            'role_name' => 'developer',
            'description' => 'Developer role',
        ]);

        $user = User::create([
            'name' => 'Developer User',
            'username' => 'devuser',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $this->actingAs($user);
        Storage::fake('public');

        $this->get('/home')
            ->assertOk()
            ->assertSee('Home')
            ->assertSee('Data Masters');

        $this->get('/master-data/processes')
            ->assertOk()
            ->assertSee('Process');

        $this->get('/master-data/operators')
            ->assertOk()
            ->assertSee('Operators');

        $this->get('/master-data/articles')
            ->assertOk()
            ->assertSee('Articles');
    }

    public function test_operator_profile_displays_history_and_gsd_details(): void
    {
        $role = Role::create([
            'role_name' => 'developer',
            'description' => 'Developer role',
        ]);

        $user = User::create([
            'name' => 'Developer User',
            'username' => 'devuser2',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $this->actingAs($user);

        $factory = Factory::create([
            'factory_name' => 'Factory A',
            'description' => 'Main factory',
        ]);

        $department = Department::create([
            'factory_id' => $factory->id,
            'department_name' => 'Sewing',
            'desription' => 'Sewing department',
        ]);

        $line = ProductionLine::create([
            'department_id' => $department->id,
            'line_name' => 'Line 1',
            'description' => 'Primary line',
        ]);

        $operator = Operator::create([
            'employee_number' => 'OP-001',
            'operator_name' => 'Budi Hartono',
            'status' => 'active',
        ]);

        $article = Article::create([
            'article_name' => 'Collar Shirt',
            'destination' => 'Knit',
            'label_number' => 'LBL-001',
            'label_number_quty' => 'QTY-001',
            'description' => 'Sample article',
            'status' => 'active',
        ]);

        $process = Process::create([
            'process_name' => 'Sewing Collar',
            'description' => 'Collar sewing process',
            'status' => 'active',
        ]);

        $category = GsdCategory::create([
            'category_name' => 'Assembly',
            'description' => 'Assembly motion data',
            'status' => 'active',
        ]);

        $element = GsdElement::create([
            'gsd_category_id' => $category->id,
            'element_name' => 'Run Stitch',
            'description' => 'Straight seam motion',
            'code' => 'RS-01',
            'tmu' => 5.5,
            'seconds' => 2.2,
            'motion_sequence' => 'A-B',
            'status' => 'active',
        ]);

        $version = ProcessVersion::create([
            'process_id' => $process->id,
            'version_number' => 1,
            'notes' => 'Baseline version',
            'status' => 'active',
            'created_by' => $user->id,
            'gsd_category_id' => $category->id,
            'gsd_element_id' => $element->id,
        ]);
        $version->gsdElements()->attach($element->id);

        PtmsReport::create([
            'report_number' => 'PTMS-2026-0001',
            'article_id' => $article->id,
            'process_version_id' => $version->id,
            'operator_id' => $operator->id,
            'factory_id' => $factory->id,
            'department_id' => $department->id,
            'line_id' => $line->id,
            'created_by' => $user->id,
            'status' => 'final',
        ]);

        $this->get('/operators/' . $operator->id)
            ->assertOk()
            ->assertSee('Budi Hartono')
            ->assertSee('Collar Shirt')
            ->assertSee('Sewing Collar')
            ->assertSee('Assembly')
            ->assertSee('Run Stitch')
            ->assertSee('RS-01');
    }

    public function test_process_and_article_master_data_can_be_created_and_updated(): void
    {
        $role = Role::create([
            'role_name' => 'admin',
            'description' => 'Admin role',
        ]);

        $user = User::create([
            'name' => 'Admin User',
            'username' => 'adminuser',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $this->actingAs($user);

        $category = GsdCategory::create([
            'category_name' => 'Assembly',
            'description' => 'Assembly motion data',
            'status' => 'active',
        ]);

        $element = GsdElement::create([
            'gsd_category_id' => $category->id,
            'element_name' => 'Run Stitch',
            'description' => 'Straight seam motion',
            'code' => 'RS-02',
            'tmu' => 5.5,
            'seconds' => 2.2,
            'motion_sequence' => 'A-B',
            'status' => 'active',
        ]);

        $this->post('/master-data/processes', [
            'process_name' => 'Sewing Collar',
            'version_number' => 2,
            'gsd_element_ids' => [$element->id],
        ])->assertRedirect(route('master-data.processes'));

        $process = Process::where('process_name', 'Sewing Collar')->firstOrFail();
        $this->assertDatabaseHas('process_versions', [
            'process_id' => $process->id,
            'version_number' => 2,
        ]);
        $this->assertDatabaseHas('process_version_gsd_elements', [
            'process_version_id' => $process->versions()->first()->id,
            'gsd_element_id' => $element->id,
        ]);

        $article = Article::create([
            'article_name' => 'Initial Article',
            'destination' => 'Knit',
            'label_number' => 'LBL-100',
            'description' => 'Initial description',
            'status' => 'active',
        ]);

        $this->put('/master-data/articles/' . $article->id, [
            'article_name' => 'Updated Article',
            'destination' => 'Woven',
            'label_number' => 'LBL-101',
            'description' => 'Updated description',
            'photo_path' => null,
        ])->assertRedirect(route('master-data.articles'));

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'article_name' => 'Updated Article',
            'label_number' => 'LBL-101',
            'label_number_quty' => 'LBL-10117596',
        ]);
    }

    public function test_operator_crud_and_dependency_safe_deletes_work(): void
    {
        $role = Role::create(['role_name' => 'admin', 'description' => 'Admin role']);
        $user = User::create([
            'name' => 'Admin User',
            'username' => 'operatoradmin',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);
        Storage::fake('public');

        $factory = Factory::where('factory_name', 'Factory 1')->firstOrFail();
        $department = Department::where('department_name', 'Sewing')->where('factory_id', $factory->id)->firstOrFail();
        $line = ProductionLine::where('line_name', 'A1')->where('department_id', $department->id)->firstOrFail();

        $this->post('/master-data/operators', [
            'operator_name' => 'Sari Dewi',
            'gender' => 'Female',
            'role' => 'Operator',
            'factory_id' => $factory->id,
            'department_id' => $department->id,
            'line_id' => $line->id,
            'photo' => $this->tinyPngUpload('operator.png'),
        ])->assertRedirect(route('master-data.operators'));

        $operator = Operator::where('operator_name', 'Sari Dewi')->firstOrFail();
        Storage::disk('public')->assertExists($operator->photo_path);
        $this->put('/master-data/operators/' . $operator->id, [
            'operator_name' => 'Sari Updated',
            'gender' => 'Female',
            'role' => 'Senior Operator',
            'factory_id' => $factory->id,
            'department_id' => $department->id,
            'line_id' => $line->id,
        ])->assertRedirect(route('master-data.operators'));

        $this->assertDatabaseHas('operators', ['id' => $operator->id, 'operator_name' => 'Sari Updated', 'role' => 'Senior Operator']);
        $this->delete('/master-data/operators/' . $operator->id)->assertRedirect(route('master-data.operators'));
        $this->assertDatabaseMissing('operators', ['id' => $operator->id]);
    }

    public function test_generated_quty_code_is_unique_and_history_blocks_deletes(): void
    {
        $role = Role::create(['role_name' => 'developer', 'description' => 'Developer role']);
        $user = User::create([
            'name' => 'Developer User',
            'username' => 'deleteadmin',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);
        Storage::fake('public');

        $article = Article::create([
            'article_name' => 'Generated Article',
            'destination' => 'Knit',
            'label_number' => 'LBL-GEN',
            'status' => 'active',
        ]);
        $this->assertSame('LBL-GEN17596', $article->fresh()->label_number_quty);

        $upload = $this->post('/master-data/articles', [
            'article_name' => 'Photo Article',
            'destination' => 'Knit',
            'label_number' => 'LBL-PHOTO',
            'photo' => $this->tinyPngUpload('article.png'),
        ]);
        $upload->assertRedirect(route('master-data.articles'));
        $photoArticle = Article::where('label_number', 'LBL-PHOTO')->firstOrFail();
        Storage::disk('public')->assertExists($photoArticle->photo_path);

        $oldPhoto = $photoArticle->photo_path;
        $this->put('/master-data/articles/' . $photoArticle->id, [
            'article_name' => 'Photo Article Updated',
            'destination' => 'Knit',
            'label_number' => 'LBL-PHOTO-UPDATED',
        ])->assertRedirect(route('master-data.articles'));
        $this->assertSame($oldPhoto, $photoArticle->fresh()->photo_path);

        $invalidUpload = $this->post('/master-data/articles', [
            'article_name' => 'Invalid Photo',
            'destination' => 'Knit',
            'label_number' => 'LBL-INVALID',
            'photo' => UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'),
        ]);
        $invalidUpload->assertSessionHasErrors('photo');

        $duplicate = $this->post('/master-data/articles', [
            'article_name' => 'Duplicate Article',
            'destination' => 'Knit',
            'label_number' => 'LBL-GEN',
        ]);
        $duplicate->assertSessionHasErrors('label_number');

        $operator = Operator::create(['employee_number' => 'OP-HISTORY', 'operator_name' => 'History Operator', 'status' => 'active']);
        $process = Process::create(['process_name' => 'History Process', 'status' => 'active']);
        $version = ProcessVersion::create(['process_id' => $process->id, 'version_number' => 1, 'status' => 'active', 'created_by' => $user->id]);
        $factory = Factory::create(['factory_name' => 'History Factory']);
        $department = Department::create(['factory_id' => $factory->id, 'department_name' => 'History Department']);
        $line = ProductionLine::create(['department_id' => $department->id, 'line_name' => 'History Line']);
        PtmsReport::create([
            'report_number' => 'PTMS-HISTORY-1',
            'article_id' => $article->id,
            'process_version_id' => $version->id,
            'operator_id' => $operator->id,
            'factory_id' => $factory->id,
            'department_id' => $department->id,
            'line_id' => $line->id,
            'created_by' => $user->id,
            'status' => 'final',
        ]);

        $this->delete('/master-data/articles/' . $article->id)->assertRedirect();
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
        $this->delete('/master-data/operators/' . $operator->id)->assertRedirect();
        $this->assertDatabaseHas('operators', ['id' => $operator->id]);
        $this->delete('/master-data/processes/' . $process->id)->assertRedirect();
        $this->assertDatabaseHas('processes', ['id' => $process->id]);
    }

    public function test_search_and_column_filter_work_for_all_master_data_pages(): void
    {
        $role = Role::create(['role_name' => 'developer', 'description' => 'Developer role']);
        $user = User::create([
            'name' => 'Developer User',
            'username' => 'filteruser',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $this->actingAs($user);
        Storage::fake('public');

        // -- Processes --
        $category = GsdCategory::create(['category_name' => 'Assembly', 'status' => 'active']);
        $element = GsdElement::create([
            'gsd_category_id' => $category->id,
            'element_name' => 'Run Stitch',
            'code' => 'RS-10',
            'tmu' => 10,
            'seconds' => 1,
            'status' => 'active',
        ]);

        $processA = Process::create(['process_name' => 'Sewing Collar', 'status' => 'active']);
        $vA = $processA->versions()->create(['version_number' => 1, 'status' => 'active', 'created_by' => $user->id]);
        $vA->gsdElements()->attach($element->id);

        $processB = Process::create(['process_name' => 'Cutting Panel', 'status' => 'active']);
        $processB->versions()->create(['version_number' => 1, 'status' => 'active', 'created_by' => $user->id]);

        // Process search
        $this->get('/master-data/processes?search=Collar')
            ->assertOk()
            ->assertSee('Sewing Collar')
            ->assertDontSee('font-medium text-slate-900">Cutting Panel</td>');

        // Process column filter
        $this->get('/master-data/processes?filter_column=process_name&filter_value=Cutting Panel')
            ->assertOk()
            ->assertSee('Cutting Panel')
            ->assertDontSee('font-medium text-slate-900">Sewing Collar</td>');

        // Process filter values JSON present
        $this->get('/master-data/processes')
            ->assertOk()
            ->assertSee('updateProcessFilterValues')
            ->assertSee('filter_column');

        // -- Operators --
        $operatorA = Operator::create(['employee_number' => 'OP-FA', 'operator_name' => 'Budi', 'gender' => 'Male', 'role' => 'Operator', 'status' => 'active']);
        $operatorB = Operator::create(['employee_number' => 'OP-FB', 'operator_name' => 'Sari', 'gender' => 'Female', 'role' => 'Supervisor', 'status' => 'active']);

        $this->get('/master-data/operators?search=Budi')
            ->assertOk()
            ->assertSee('Budi')
            ->assertDontSee('font-medium text-slate-900">Sari</td>');

        $this->get('/master-data/operators?filter_column=gender&filter_value=Female')
            ->assertOk()
            ->assertSee('Sari')
            ->assertDontSee('font-medium text-slate-900">Budi</td>');

        // -- Articles --
        $articleA = Article::create(['article_name' => 'Office Shirt', 'destination' => 'Knit', 'label_number' => 'LBL-A1', 'status' => 'active']);
        $articleB = Article::create(['article_name' => 'Casual Pants', 'destination' => 'Woven', 'label_number' => 'LBL-B1', 'status' => 'active']);

        $this->get('/master-data/articles?search=Office')
            ->assertOk()
            ->assertSee('Office Shirt')
            ->assertDontSee('font-medium text-slate-900">Casual Pants</td>');

        $this->get('/master-data/articles?filter_column=destination&filter_value=Woven')
            ->assertOk()
            ->assertSee('Casual Pants')
            ->assertDontSee('font-medium text-slate-900">Office Shirt</td>');

        // Clear filter returns all
        $this->get('/master-data/articles')
            ->assertOk()
            ->assertSee('Office Shirt')
            ->assertSee('Casual Pants');
    }
}
