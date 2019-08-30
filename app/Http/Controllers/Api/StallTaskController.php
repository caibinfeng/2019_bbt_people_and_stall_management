<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Schedule;
use App\Models\StallTask;
use App\Models\Calendar;
use App\Models\Stall;


class StallTaskController extends Controller
{
    private function dateToWeek($date){
        $start_date=Calendar::where('date','<',$date)->orderBy('date','desc')->first();
        return ceil((strtotime($date)-strtotime($start_date+' -1 day'))/(604800));
    }

    public function newTask(Request $request){
        //验证
        StallTask::create([
            'stall_id'=>$request->stall_id,
            'location'=>$request->location,
            'date'=>$request->date,
            'number'=>$request->number,
            'start'=>$request->start,
            'end'=>$request->end
        ]);
        return $this->response->noContent();
    }

    public function deleteTask($id){
        //验证
        StallTask::find($id)->delete();
        return $this->response->noContent();
    }

    public function updateTask($id,Request $request){
        //验证
        //感觉不能使用更新
        StallTask::create([
            'location'=>$request->location,
            'date'=>$request->date,
            'number'=>$request->number,
            'start'=>$request->start,
            'end'=>$request->end
        ]);
        return $this->response->noContent();
    }

    public function createList(Request $request){
        //验证
        $id=$request->id;
        $group=$request->group;

        $task=StallTask::find($id);
        $week=$this->dateToWeek($task->date);
        $day=date('w',$task->date);
        if ($day==0)$day=7;
        $stall=$task->stall;
        $tasks=$stall->tasks()->select('id')->get();
        $task_ids=$tasks->flatMap(function($task){
            return $task->id;
        })->toArray();
        $already_users_ids=$stall->userStallTask()->pluck('user_id')->toArray();
        $already_users_str=implode(',',$already_users_ids);
        $users=User::with([
            'stallNumber',
        ])->where('group_id',$group)
          ->whereHas('stallNumber',function($q){
            $q->where('verified',1);
        })->whereHas('schedules',function($q)use($week,$day,$task){
            $q->where('week',$week)
              ->where('day',$day)
              ->whereBetween('class',[$task->start,$task->end]);
        })->get()
          ->shuffle()
          ->sortBy(function($user,$key)use($already_users_ids){
                if (in_array($user->id,$already_users_ids)) return PHP_INT_MAX;
                else return $user->stallNumber->number;
          })
          ->take($task->number);
        // $users2=User::join('stallNumber','users.id','=','stallNumber')



        $users->each(function($user,$key)use($task){
            $user->stallTasks()->attach($task->id);
        });
        return $this->response->noContent();
    }

    public function addTaskAdmin(Request $request){
        //验证 
        $user_id=$request->user_id;
        $task_id=$request->task_id;
        $user=User::find($user_id);
        $user->stallTasks()->attach($task_id,['role'=>1]);
        $user->stallNumber()->increment('number');
        return $this->response->noContent();
    }

    public function checkIn(Request $request){
        //验证
        $user_id=$request->user_id;
        $task_id=$request->task_id;
        $user=User::find($user_id);
        $user->stallTasks()->updateExistingPivot($task_id, ['check_in'=>1]);
        $user->stallNumber->increment('number');
        return $this->response->noContent();
    }

    public function showTask($id){
        $task=StallTask::find($id);
        return $this->response->array($task->toArray());
    }

    public function showTaskList($id){
        $stall=Stall::find($id);
        $tasks=$stall->stallTasks;
        return $this->response->array($tasks->toArray());
    }

    public function showTaskMember($id){
        $users=User::with([
            'details'=>function($q){
                $q->select(['user_id','name', 'sex' ,'mobile']);
            },
            'department'=>function($q){
                $q->select(['user_id','name']);
            },
            'stallNumber'=>function($q){
                $q->select(['user_id','role','check_in']);
            },
        ])->whereHas('stallTasks',function($q)use($id){
            $q->where('id',$id);
        });
        return $this->response->array($users->toArray());
    }

    public function testCreate($id){
        $task=StallTask::find($id);
        $stall=$task->stall;
        $already_users_ids=$stall->userStallTask()->pluck('user_id')->toArray();
        $already_users_str=implode(',',$already_users_ids);
        dd($already_users_str);

        
    }   

}
