<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Machine-checkable feature entitlements per package. `features` stays as the
 * marketing bullet list; `feature_keys` is the enforceable set (e.g.
 * ["assignments","messaging","advanced_reports"]) consumed by
 * SubscriptionGate::hasFeature and the subscription.feature middleware.
 * null = not configured => all features granted (backward compatible).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sys_packages', 'feature_keys')) {
            return;
        }

        Schema::table('sys_packages', function (Blueprint $table) {
            $table->json('feature_keys')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sys_packages', 'feature_keys')) {
            return;
        }

        Schema::table('sys_packages', function (Blueprint $table) {
            $table->dropColumn('feature_keys');
        });
    }
};
