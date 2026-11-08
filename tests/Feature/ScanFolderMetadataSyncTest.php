<?php

namespace Tests\Feature;

use App\Models\DriveConfig;
use App\Models\Upload;
use App\Models\User;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ScanFolderMetadataSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_menyimpan_perubahan_nama_dan_folder_dari_drive(): void
    {
        $admin = User::create([
            'username' => 'admin_sync_test',
            'nama' => 'Admin Sync Test',
            'email' => 'admin_sync_test@example.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        DriveConfig::create([
            'folder_id' => 'folder-utama',
            'folder_link' => 'https://drive.google.com/drive/folders/folder-utama',
        ]);

        $upload = Upload::create([
            'drive_file_id' => 'file-123',
            'filename' => 'nama-lama.docx',
            'subfolder' => 'Folder Lama',
            'uploader_name' => 'Google Drive',
            'upload_time' => '01-01-2026 10:00',
            'status' => 'pending',
        ]);

        $this->mock(GoogleDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listSubfolders')->once()->andReturn([]);
            $mock->shouldReceive('listFilesRecursive')->once()->andReturn([
                [
                    'id' => 'file-123',
                    'name' => 'nama-baru.docx',
                    'subfolder' => 'Folder Baru',
                    'createdTime' => '2026-01-01T10:00:00Z',
                ],
            ]);
        });

        $response = $this->actingAs($admin)->postJson('/admin/scan-folder', [
            'scan_all' => true,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('uploads', [
            'id' => $upload->id,
            'drive_file_id' => 'file-123',
            'filename' => 'nama-baru.docx',
            'subfolder' => 'Folder Baru',
        ]);

        $this->assertDatabaseCount('uploads', 1);
    }

    public function test_scan_berulang_tidak_membuat_baris_ganda(): void
    {
        $admin = $this->buatAdmin('admin_scan_ulang');

        DriveConfig::create([
            'folder_id' => 'folder-utama',
            'folder_link' => 'https://drive.google.com/drive/folders/folder-utama',
        ]);

        $this->mock(GoogleDriveService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listSubfolders')->twice()->andReturn([]);
            $mock->shouldReceive('listFilesRecursive')->twice()->andReturn([
                [
                    'id' => 'file-999',
                    'name' => 'dokumen.docx',
                    'subfolder' => '(folder utama)',
                    'createdTime' => '2026-01-01T10:00:00Z',
                ],
            ]);
        });

        $this->actingAs($admin)
            ->postJson('/admin/scan-folder', ['scan_all' => true])
            ->assertOk();
        $this->assertDatabaseCount('uploads', 1);

        $this->actingAs($admin)
            ->postJson('/admin/scan-folder', ['scan_all' => true])
            ->assertOk();
        $this->assertDatabaseCount('uploads', 1);
        $this->assertDatabaseHas('uploads', ['drive_file_id' => 'file-999']);
    }

    public function test_scan_dengan_file_ganda_dalam_satu_respons_tetap_satu_baris(): void
    {
        $admin = $this->buatAdmin('admin_file_ganda');

        DriveConfig::create([
            'folder_id' => 'folder-utama',
            'folder_link' => 'https://drive.google.com/drive/folders/folder-utama',
        ]);

        $file = [
            'id' => 'file-777',
            'name' => 'dokumen.docx',
            'subfolder' => '(folder utama)',
            'createdTime' => '2026-01-01T10:00:00Z',
        ];

        $this->mock(GoogleDriveService::class, function (MockInterface $mock) use ($file): void {
            $mock->shouldReceive('listSubfolders')->once()->andReturn([]);
            $mock->shouldReceive('listFilesRecursive')->once()->andReturn([$file, $file]);
        });

        $response = $this->actingAs($admin)
            ->postJson('/admin/scan-folder', ['scan_all' => true]);

        $response->assertOk();
        $this->assertDatabaseCount('uploads', 1);
        $this->assertDatabaseHas('uploads', ['drive_file_id' => 'file-777']);
    }

    public function test_database_menolak_drive_file_id_ganda(): void
    {
        $this->buatUpload('file-ganda');

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->buatUpload('file-ganda');
    }

    public function test_id_drive_beda_huruf_besar_kecil_dianggap_berbeda(): void
    {
        $this->buatUpload('AbCdEf');
        $this->buatUpload('abcdef');

        $this->assertDatabaseCount('uploads', 2);
    }

    private function buatAdmin(string $username): User
    {
        return User::create([
            'username' => $username,
            'nama' => 'Admin ' . $username,
            'email' => $username . '@example.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function buatUpload(string $driveFileId): Upload
    {
        return Upload::create([
            'drive_file_id' => $driveFileId,
            'filename' => 'dokumen.docx',
            'subfolder' => '(folder utama)',
            'uploader_name' => 'Google Drive',
            'upload_time' => '01-01-2026 10:00',
            'status' => 'pending',
        ]);
    }
}
