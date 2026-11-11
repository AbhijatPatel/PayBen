<?php
// bills/gas

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!isLoggedIn()) jsonResponse(['success'=>false,'message'=>'Authentication required.'],401);
$userId=(int)$_SESSION['user_id'];
$pdo=getDBConnection();

if ($_SERVER['REQUEST_METHOD']==='GET' && ($_GET['action']??'')==='fetch') {
    $provider_id=(int)($_GET['provider_id']??0); $consumer_number=trim($_GET['consumer_number']??'');
    if (!$provider_id||!$consumer_number) jsonResponse(['success'=>false,'message'=>'Provider ID and consumer number required.'],422);
    try {
        $p=$pdo->prepare("SELECT id,name,state FROM utility_providers WHERE id=? AND type='gas' AND status='active' LIMIT 1");
        $p->execute([$provider_id]); $prov=$p->fetch();
        if (!$prov) jsonResponse(['success'=>false,'message'=>'Gas provider not found.'],404);
        $b=$pdo->prepare("SELECT * FROM sample_bills WHERE provider_id=? AND consumer_number=? AND status='unpaid' LIMIT 1");
        $b->execute([$provider_id,$consumer_number]); $bill=$b->fetch();
        if (!$bill) jsonResponse(['success'=>false,'message'=>'No pending gas bill found for this consumer number.'],404);
        jsonResponse(['success'=>true,'message'=>'Gas bill fetched.','data'=>['bill_id'=>$bill['id'],'consumer_name'=>$bill['consumer_name'],
            'consumer_number'=>$bill['consumer_number'],'provider'=>$prov['name'],'state'=>$prov['state'],
            'bill_amount'=>$bill['bill_amount'],'amount_formatted'=>formatCurrency((float)$bill['bill_amount']),
            'bill_date'=>$bill['bill_date'],'due_date'=>$bill['due_date'],'status'=>$bill['status']]]);
    } catch(PDOException $e){ error_log($e->getMessage()); jsonResponse(['success'=>false,'message'=>'Server error.'],500); }
} elseif ($_SERVER['REQUEST_METHOD']==='POST') {
    $input=json_decode(file_get_contents('php://input'),true)??$_POST;
    if (($input['action']??'')!=='pay') jsonResponse(['success'=>false,'message'=>'Use action=pay.'],400);
    $bill_id=(int)($input['bill_id']??0);
    if (!$bill_id) jsonResponse(['success'=>false,'message'=>'Valid bill_id required.'],422);
    try {
        $pdo->beginTransaction();
        $w=$pdo->prepare("SELECT id,balance FROM wallets WHERE user_id=? FOR UPDATE"); $w->execute([$userId]); $wallet=$w->fetch();
        if (!$wallet){$pdo->rollBack();jsonResponse(['success'=>false,'message'=>'Wallet not found.'],404);}
        $b=$pdo->prepare("SELECT sb.*,up.name as provider_name FROM sample_bills sb JOIN utility_providers up ON sb.provider_id=up.id WHERE sb.id=? AND sb.status='unpaid' AND up.type='gas' LIMIT 1");
        $b->execute([$bill_id]); $bill=$b->fetch();
        if (!$bill){$pdo->rollBack();jsonResponse(['success'=>false,'message'=>'Bill not found or already paid.'],404);}
        $amount=(float)$bill['bill_amount'];
        if ((float)$wallet['balance']<$amount){$pdo->rollBack();jsonResponse(['success'=>false,'message'=>'Insufficient wallet balance.','available'=>formatCurrency((float)$wallet['balance']),'required'=>formatCurrency($amount)],402);}
        $newBalance=(float)$wallet['balance']-$amount; $txnId=generateTransactionID('GB');
        $pdo->prepare("UPDATE wallets SET balance=? WHERE id=?")->execute([$newBalance,$wallet['id']]);
        $pdo->prepare("INSERT INTO wallet_transactions(user_id,wallet_id,type,amount,description,reference_id,balance_after)VALUES(?,?,'debit',?,?,?,?)")
            ->execute([$userId,$wallet['id'],$amount,"Gas Bill - {$bill['provider_name']} #{$bill['consumer_number']} [SIMULATED]",$txnId,$newBalance]);
        $pdo->prepare("INSERT INTO transactions(user_id,transaction_id,type,mobile_or_consumer_number,operator_or_provider,amount,status,payment_method,description)VALUES(?,?,'gas',?,?,?,'SUCCESS','Wallet',?)")
            ->execute([$userId,$txnId,$bill['consumer_number'],$bill['provider_name'],$amount,"Gas Bill Payment [SIMULATED]"]);
        $pdo->prepare("UPDATE sample_bills SET status='paid' WHERE id=?")->execute([$bill_id]);
        $pdo->commit();
        jsonResponse(['success'=>true,'message'=>'Gas bill paid successfully! [SIMULATED PAYMENT]','data'=>['transaction_id'=>$txnId,'consumer_number'=>$bill['consumer_number'],'provider'=>$bill['provider_name'],'amount'=>$amount,'amount_formatted'=>formatCurrency($amount),'new_balance'=>$newBalance,'new_balance_fmt'=>formatCurrency($newBalance),'status'=>'SUCCESS','simulated'=>true,'timestamp'=>date('Y-m-d H:i:s')]]);
    } catch(PDOException $e){ if($pdo->inTransaction())$pdo->rollBack(); error_log($e->getMessage()); jsonResponse(['success'=>false,'message'=>'Payment failed.'],500); }
} else { jsonResponse(['success'=>false,'message'=>'Invalid request method.'],405); }

