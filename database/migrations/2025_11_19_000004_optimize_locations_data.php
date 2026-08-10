<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
   {
        // 原校园网地点名称优化，通用化后无对应数据（updateLocationName 找不到匹配即空操作），
        // 直接跳过避免无意义的批量 UPDATE。
        return;

        // --- 以下为历史逻辑，保留备查 ---
        $this->updateLocationName('第一教学楼', '1教');
        $this->updateLocationName('第二教学楼', '2教');
        $this->updateLocationName('第三教学楼', '3教');
        $this->updateLocationName('第四教学楼', '4教');
        $this->updateLocationName('第五教学楼', '5教');
        $this->updateLocationName('第六教学楼', '6教');
        $this->updateLocationName('第七教学楼', '7教');
        
        // 老校区学生宿舍优化
        $this->updateLocationName('第一学生宿舍', '1栋');
        $this->updateLocationName('第二学生宿舍', '2栋');
        $this->updateLocationName('第三学生宿舍', '3栋');
        $this->updateLocationName('第四学生宿舍', '4栋');
        $this->updateLocationName('第五学生宿舍', '5栋');
        $this->updateLocationName('第六学生宿舍', '6栋');
        $this->updateLocationName('第七学生宿舍', '7栋');
        $this->updateLocationName('第八学生宿舍', '8栋');
        $this->updateLocationName('第九学生宿舍', '9栋');
        $this->updateLocationName('第十学生宿舍', '10栋');
        
        // 新校区教学楼优化
        $this->updateLocationName('第八教学楼', '8教');
        $this->updateLocationName('第九教学楼', '9教');
        $this->updateLocationName('第十教学楼', '10教');
        $this->updateLocationName('第十一教学楼', '11教');
        $this->updateLocationName('第十二教学楼', '12教');
        $this->updateLocationName('第十三教学楼', '13教');
        $this->updateLocationName('第十四教学楼', '14教');
        
        // 新校区学生宿舍优化
        $this->updateLocationName('第十一学生宿舍', '11栋');
        $this->updateLocationName('第十二学生宿舍', '12栋');
        $this->updateLocationName('第十三学生宿舍', '13栋');
        $this->updateLocationName('第十四学生宿舍', '14栋');
        $this->updateLocationName('第十五学生宿舍', '15栋');
        $this->updateLocationName('第十六学生宿舍', '16栋');
        $this->updateLocationName('第十七学生宿舍', '17栋');
        $this->updateLocationName('第十八学生宿舍', '18栋');
        
        // 东盟校区教学楼优化
        $this->updateLocationName('A教学楼', 'A教');
        $this->updateLocationName('B教学楼', 'B教');
        $this->updateLocationName('C教学楼', 'C教');
        $this->updateLocationName('D教学楼', 'D教');
        $this->updateLocationName('E教学楼', 'E教');
        $this->updateLocationName('F教学楼', 'F教');
        $this->updateLocationName('G教学楼', 'G教');
        $this->updateLocationName('H教学楼', 'H教');
        $this->updateLocationName('I教学楼', 'I教');
        $this->updateLocationName('J教学楼', 'J教');
        
        // 东盟校区学生宿舍优化
        $this->updateLocationName('第十九学生宿舍', '19栋');
        $this->updateLocationName('第二十学生宿舍', '20栋');
        
        // 优化汇总地址
        $this->updateLocationName('老校区1-7教', '1-7教');
        $this->updateLocationName('新校区8-14教', '8-14教');
        $this->updateLocationName('东盟A-J教学楼', 'A-J教');
        $this->updateLocationName('老校区1-10舍', '1-10栋');
        $this->updateLocationName('新校区11-18舍', '11-18栋');
        $this->updateLocationName('东盟19-20舍', '19-20栋');
    }

    /**
     * 更新位置名称
     */
    private function updateLocationName($oldName, $newName): void
    {
        DB::table('locations')
            ->where('name', $oldName)
            ->update([
                'name' => $newName,
                'building_code' => $newName,
                'updated_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 恢复原始数据格式
        $this->restoreLocationData();
    }

    /**
     * 恢复位置数据
     */
    private function restoreLocationData(): void
    {
        // 恢复老校区教学楼
        $this->restoreLocationName('1教', '第一教学楼');
        $this->restoreLocationName('2教', '第二教学楼');
        $this->restoreLocationName('3教', '第三教学楼');
        $this->restoreLocationName('4教', '第四教学楼');
        $this->restoreLocationName('5教', '第五教学楼');
        $this->restoreLocationName('6教', '第六教学楼');
        $this->restoreLocationName('7教', '第七教学楼');
        
        // 恢复老校区学生宿舍
        $this->restoreLocationName('1栋', '第一学生宿舍');
        $this->restoreLocationName('2栋', '第二学生宿舍');
        $this->restoreLocationName('3栋', '第三学生宿舍');
        $this->restoreLocationName('4栋', '第四学生宿舍');
        $this->restoreLocationName('5栋', '第五学生宿舍');
        $this->restoreLocationName('6栋', '第六学生宿舍');
        $this->restoreLocationName('7栋', '第七学生宿舍');
        $this->restoreLocationName('8栋', '第八学生宿舍');
        $this->restoreLocationName('9栋', '第九学生宿舍');
        $this->restoreLocationName('10栋', '第十学生宿舍');
        
        // 恢复新校区教学楼
        $this->restoreLocationName('8教', '第八教学楼');
        $this->restoreLocationName('9教', '第九教学楼');
        $this->restoreLocationName('10教', '第十教学楼');
        $this->restoreLocationName('11教', '第十一教学楼');
        $this->restoreLocationName('12教', '第十二教学楼');
        $this->restoreLocationName('13教', '第十三教学楼');
        $this->restoreLocationName('14教', '第十四教学楼');
        
        // 恢复新校区学生宿舍
        $this->restoreLocationName('11栋', '第十一学生宿舍');
        $this->restoreLocationName('12栋', '第十二学生宿舍');
        $this->restoreLocationName('13栋', '第十三学生宿舍');
        $this->restoreLocationName('14栋', '第十四学生宿舍');
        $this->restoreLocationName('15栋', '第十五学生宿舍');
        $this->restoreLocationName('16栋', '第十六学生宿舍');
        $this->restoreLocationName('17栋', '第十七学生宿舍');
        $this->restoreLocationName('18栋', '第十八学生宿舍');
        
        // 恢复东盟校区教学楼
        $this->restoreLocationName('A教', 'A教学楼');
        $this->restoreLocationName('B教', 'B教学楼');
        $this->restoreLocationName('C教', 'C教学楼');
        $this->restoreLocationName('D教', 'D教学楼');
        $this->restoreLocationName('E教', 'E教学楼');
        $this->restoreLocationName('F教', 'F教学楼');
        $this->restoreLocationName('G教', 'G教学楼');
        $this->restoreLocationName('H教', 'H教学楼');
        $this->restoreLocationName('I教', 'I教学楼');
        $this->restoreLocationName('J教', 'J教学楼');
        
        // 恢复东盟校区学生宿舍
        $this->restoreLocationName('19栋', '第十九学生宿舍');
        $this->restoreLocationName('20栋', '第二十学生宿舍');
        
        // 恢复汇总地址
        $this->restoreLocationName('1-7教', '老校区1-7教');
        $this->restoreLocationName('8-14教', '新校区8-14教');
        $this->restoreLocationName('A-J教', '东盟A-J教学楼');
        $this->restoreLocationName('1-10栋', '老校区1-10舍');
        $this->restoreLocationName('11-18栋', '新校区11-18舍');
        $this->restoreLocationName('19-20栋', '东盟19-20舍');
    }

    /**
     * 恢复位置名称
     */
    private function restoreLocationName($newName, $oldName): void
    {
        DB::table('locations')
            ->where('name', $newName)
            ->update([
                'name' => $oldName,
                'building_code' => $oldName,
                'updated_at' => now()
            ]);
    }
};
