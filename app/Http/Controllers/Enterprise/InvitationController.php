<?php

declare(strict_types=1);
namespace App\Http\Controllers\Enterprise;
use App\Http\Controllers\Controller;use App\Nexora\Enterprise\Services\InvitationManager;use Illuminate\Http\RedirectResponse;use Illuminate\Http\Request;
final class InvitationController extends Controller { public function accept(Request $request,string $token,InvitationManager $invitations):RedirectResponse{$invitations->accept($token,$request->user());return redirect('/admin')->with('success','Organization invitation accepted.');} }
