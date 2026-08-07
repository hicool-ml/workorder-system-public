<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 报修人短信满意度回写
     * - sms_acceptance_sent_at: 受理短信发送时间，保证只发一次 + 回复关联
     * - sms_survey_sent_at:     满意度调查短信发送时间，保证只发一次 + 回复关联
     * - sms_satisfaction:       报修人回复的满意度（1=满意, 0=不满意, null=未回复）
     * - sms_satisfaction_at:    回复时间
     */
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->datetime('sms_acceptance_sent_at')->nullable()->after('is_user_signed');
            $table->datetime('sms_survey_sent_at')->nullable()->after('sms_acceptance_sent_at');
            $table->tinyInteger('sms_satisfaction')->nullable()->comment('报修人短信满意度：1满意/0不满意/null未回复')->after('sms_survey_sent_at');
            $table->datetime('sms_satisfaction_at')->nullable()->after('sms_satisfaction');
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropColumn(['sms_acceptance_sent_at', 'sms_survey_sent_at', 'sms_satisfaction', 'sms_satisfaction_at']);
        });
    }
};
