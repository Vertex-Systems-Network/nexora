<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmNote;
use App\Nexora\Crm\Services\CrmActivityService;
use App\Nexora\Crm\Contracts\CrmTimelineContract;
use App\Nexora\Crm\Support\CrmEntityTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ActivityController extends Controller
{
    public function store(Request $request, CrmActivityService $activities): RedirectResponse
    {
        $data=$request->validate([
            'subject_type'=>['required','in:organization,contact,lead,opportunity'],'subject_id'=>['required','uuid'],'type'=>['required','in:call,email,meeting,task,note,other'],'title'=>['required','string','max:220'],
            'body'=>['nullable','string','max:10000'],'occurred_at'=>['nullable','date'],'due_at'=>['nullable','date'],'completed'=>['nullable','boolean'],
        ]);
        $activities->create($data['subject_type'],$data['subject_id'],[
            'type'=>$data['type'],'title'=>$data['title'],'body'=>$data['body']??null,'occurred_at'=>$data['occurred_at']??now(),'due_at'=>$data['due_at']??null,'completed_at'=>($data['completed']??false)?now():null,
        ],$request->user()?->id);
        return back()->with('success','Activity recorded.');
    }

    public function note(Request $request, CrmTimelineContract $timeline): RedirectResponse
    {
        $data=$request->validate(['subject_type'=>['required','in:organization,contact,lead,opportunity'],'subject_id'=>['required','uuid'],'body'=>['required','string','max:20000'],'pinned'=>['nullable','boolean']]);
        CrmEntityTypes::findOrFail($data['subject_type'],$data['subject_id']);
        $note=CrmNote::query()->create(['subject_type'=>$data['subject_type'],'subject_id'=>$data['subject_id'],'body'=>$data['body'],'pinned'=>(bool)($data['pinned']??false),'author_id'=>$request->user()?->id]);
        $timeline->record($data['subject_type'],$data['subject_id'],'note.created','Note added',mb_strimwidth($note->body,0,240,'…'),['note_id'=>$note->id,'pinned'=>$note->pinned],$request->user()?->id);
        return back()->with('success','Note added.');
    }
}
