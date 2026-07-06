<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/auth.php';

$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri      = preg_replace('#^/api#','',$uri);
$method   = $_SERVER['REQUEST_METHOD'];
$segments = array_values(array_filter(explode('/',trim($uri,'/'))));
$s0=$segments[0]??''; $s1=$segments[1]??''; $s2=$segments[2]??'';

try { match(true) {
    // Auth
    $method==='POST'   && $s0==='auth'    && $s1==='login'
        => require __DIR__.'/routes/auth_login.php',
        $method==='POST'   && $s0==='auth'    && $s1==='register'
            => require __DIR__.'/routes/auth_register.php',
        $method==='GET'    && $s0==='auth'    && $s1==='verify'
            => require __DIR__.'/routes/auth_verify.php',
        $method==='POST'   && $s0==='auth'    && $s1==='resend-verification'
            => require __DIR__.'/routes/auth_resend.php',
        $method==='POST'   && $s0==='auth'    && $s1==='forgot-password'
            => require __DIR__.'/routes/auth_forgot.php',
        $method==='POST'   && $s0==='auth'    && $s1==='reset-password'
            => require __DIR__.'/routes/auth_reset.php',

    // Queue
    $method==='GET'    && $s0==='queue'   && $s1==='next'
        => require __DIR__.'/routes/queue_next.php',
    $method==='POST'   && $s0==='queue'   && $s2==='result'
        => (fn()=>($GLOBALS['task_id']=(int)$s1)&&require __DIR__.'/routes/queue_result.php')(),

    // Queue — gestão
        $method==='GET'    && $s0==='queue'   && !$s1
            => require __DIR__.'/routes/queue_list.php',
        $method==='DELETE' && $s0==='queue'   && $s1==='all'
            => require __DIR__.'/routes/queue_cancel_all.php',
        $method==='DELETE' && $s0==='queue'   && $s1
            => (fn()=>($GLOBALS['task_id']=(int)$s1)&&require __DIR__.'/routes/queue_cancel.php')(),

    // Leads
    $method==='GET'    && $s0==='leads'   && !$s1
        => require __DIR__.'/routes/leads_list.php',
    $method==='POST'   && $s0==='leads'   && !$s1
        => require __DIR__.'/routes/leads_create.php',
    $method==='DELETE' && $s0==='leads'   && $s1 && !$s2
        => (fn()=>($GLOBALS['lead_id']=(int)$s1)&&require __DIR__.'/routes/leads_delete.php')(),
    $method==='PUT'    && $s0==='leads'   && $s1 && $s2==='status'
        => (fn()=>($GLOBALS['lead_id']=(int)$s1)&&require __DIR__.'/routes/leads_status.php')(),

    // Message Templates
    $method==='GET'    && $s0==='templates' && !$s1
        => require __DIR__.'/routes/templates_list.php',
    $method==='POST'   && $s0==='templates' && !$s1
        => require __DIR__.'/routes/templates_create.php',
    $method==='PUT'    && $s0==='templates' && $s1
        => (fn()=>($GLOBALS['template_id']=(int)$s1)&&require __DIR__.'/routes/templates_update.php')(),
    $method==='DELETE' && $s0==='templates' && $s1
        => (fn()=>($GLOBALS['template_id']=(int)$s1)&&require __DIR__.'/routes/templates_delete.php')(),

    // Image Templates
    $method==='GET'    && $s0==='image-templates' && !$s1
        => require __DIR__.'/routes/image_templates_list.php',
    $method==='POST'   && $s0==='image-templates' && !$s1
        => require __DIR__.'/routes/image_templates_create.php',
    $method==='PUT'    && $s0==='image-templates' && $s1
        => (fn()=>($GLOBALS['image_template_id']=(int)$s1)&&require __DIR__.'/routes/image_templates_update.php')(),
    $method==='DELETE' && $s0==='image-templates' && $s1
        => (fn()=>($GLOBALS['image_template_id']=(int)$s1)&&require __DIR__.'/routes/image_templates_delete.php')(),

        // Campaigns
        $method==='GET'    && $s0==='campaigns' && !$s1
            => require __DIR__.'/routes/campaigns_list.php',
        $method==='POST'   && $s0==='campaigns' && !$s1
            => require __DIR__.'/routes/campaigns_create.php',
        $method==='PUT'    && $s0==='campaigns' && $s1 && !$s2
            => (fn()=>($GLOBALS['campaign_id']=(int)$s1)&&require __DIR__.'/routes/campaigns_update.php')(),
        $method==='DELETE' && $s0==='campaigns' && $s1 && !$s2
            => (fn()=>($GLOBALS['campaign_id']=(int)$s1)&&require __DIR__.'/routes/campaigns_delete.php')(),
        $method==='POST'   && $s0==='campaigns' && $s1 && $s2==='leads'
            => (fn()=>($GLOBALS['campaign_id']=(int)$s1)&&require __DIR__.'/routes/campaigns_add_leads.php')(),

        // Settings
        $method==='GET' && $s0==='settings' && !$s1
            => require __DIR__.'/routes/settings_get.php',
        $method==='PUT' && $s0==='settings' && !$s1
            => require __DIR__.'/routes/settings_update.php',
        $method==='PUT' && $s0==='settings' && $s1==='password'
            => require __DIR__.'/routes/settings_password.php',

        // Debug (apagar depois)
        $method==='GET' && $s0==='debug' && $s1==='vp'
            => require __DIR__.'/routes/debug_vp.php',
        $method==='GET' && $s0==='debug' && $s1==='admin'
            => require __DIR__.'/routes/debug_admin.php',

        // Stripe
        $method==='POST' && $s0==='stripe' && $s1==='checkout'
            => require __DIR__.'/routes/stripe_checkout.php',
        $method==='POST' && $s0==='stripe' && $s1==='webhook'
            => require __DIR__.'/routes/stripe_webhook.php',
        $method==='POST' && $s0==='stripe' && $s1==='portal'
            => require __DIR__.'/routes/stripe_portal.php',

        // Platform (público)
        $method==='GET' && $s0==='platform' && $s1==='announcement'
            => require __DIR__.'/routes/platform_announcement.php',

        // Admin
        $method==='GET'  && $s0==='admin' && $s1==='stats'
            => require __DIR__.'/routes/admin_stats.php',
        ($method==='GET' || $method==='PUT' || $method==='DELETE') && $s0==='admin' && $s1==='users'
            => (function() use ($s2){ $GLOBALS['admin_user_id']=(int)($s2??0); require __DIR__.'/routes/admin_users.php'; })(),
        $method==='POST' && $s0==='admin' && $s1==='send-email'
            => require __DIR__.'/routes/admin_send_email.php',
        ($method==='GET' || $method==='PUT') && $s0==='admin' && $s1==='settings'
            => require __DIR__.'/routes/admin_settings.php',

        default => json_error(404,'Rota não encontrada')
}; } catch(Throwable $e) { json_error(500,$e->getMessage()); }

function json_ok(mixed $data,int $status=200):void{http_response_code($status);echo json_encode(['ok'=>true,'data'=>$data]);exit;}
function json_error(int $status,string $message):void{http_response_code($status);echo json_encode(['ok'=>false,'error'=>$message]);exit;}
