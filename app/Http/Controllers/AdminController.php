<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin;
use App\Models\Community;
use App\Models\Del_Reason;
use App\Models\Del_title;
use App\Models\Replie;
use App\Models\Like;
use App\Models\Report;
use App\Models\Info;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class AdminController extends Controller
{
    
    // 어드민페이지 시작
    public function adminchk(Request $req)
    {
        Auth::user();
        $chk = Admin::where('u_id',Auth::user()->id)->get();
        if($req->id==Auth::user()->id && $req->nick === Auth::user()->nick && $req->email === Auth::user()->email && count($chk)>0){
            // 오늘 탈퇴자수
            $out = User::withTrashed()
                ->select('deleted_at')
                ->where('deleted_at', '>=', $req->today)
                ->count ();
            // 오늘 가입자수
            $in = User::
                where('created_at','>=', $req->today)
                ->count();
            // 신규질문
            $data = Community::
                select('users.email','community.*')
                ->where('community.flg', "3")
                ->join('users', 'community.u_id', '=', 'users.id')
                ->where('community.admin_flg', 0)
                ->orderby('community.created_at','asc')
                ->limit(5)
                ->get();
            $datacnt = Community::
                select('users.email','community.*')
                ->where('community.flg', "3")
                ->join('users', 'community.u_id', '=', 'users.id')
                ->where('community.admin_flg', 0)
                ->orderby('community.created_at','asc')
                ->count();
            // 신고목록
            $report = Report::
                select('users.email','reports.*')
                ->where('reports.admin_flg', 0)
                ->join('users', 'reports.u_id', '=', 'users.id')
                ->orderby('reports.created_at','asc')
                ->limit(5)
                ->get();
            $reportcnt = Report::
                select('users.email','reports.*')
                ->where('reports.admin_flg', 0)
                ->join('users', 'reports.u_id', '=', 'users.id')
                ->orderby('reports.created_at','asc')
                ->count();
            return response()->json([
                'code' => '0',
                'sign_cnt' => $in,
                'drop_cnt' => $out,
                'data' => $data,
                'd_cnt' => $datacnt,
                'r_data' => $report,
                'r_cnt' => $reportcnt,
            ], 200);
        }else{
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '인증과정중 문제가 발생했습니다. 다시 한번 로그인후 이용해 주세요'
            ], 400);
        }
    }
    // modal용 report data획득
    public function reportget(Request $req){
        if($req->flg==="0"){
            $data = Community::withTrashed()
            ->select('users.email','community.*','reports.restraint_at')
            ->where('community.id',$req->b_id)
            ->join('users', 'community.u_id', '=', 'users.id')
            ->leftjoin('reports', 'community.u_id', '=', 'reports.r_id')
            ->first();
        }else{
            $data = Replie::withTrashed()
            ->select('users.email','replies.*','reports.restraint_at')
            ->where('replies.id',$req->b_id)
            ->join('users', 'replies.u_id', '=', 'users.id')
            ->leftjoin('reports', 'replies.u_id', '=', 'reports.r_id')
            ->first();
        }
        return response()->json([
            'code' => '0',
            'data' => $data,
        ], 200);
    }
    // 답변달기
    public function answerdata(Request $req){
        try {
            // 리퀘스트온 아이디 값으로 커뮤니티 테이블 조회
            $data = Community::withTrashed()
            ->where('id',$req->id)
            ->first();
            if($data->deleted_at !== null){
                return response()->json([
                    'code' => '1',
                    'errorMsg' => '유저가 삭제한 게시글입니다.'
                ], 400);
            }
            // 트랜잭션시작
            DB::beginTransaction();
            // 리퀘스트온 replie추가
            $replie = $req->only('replie');
            // 인서트할 데이터 추가
            $replie['b_id'] = $req->id;
            $replie['u_id'] = Auth::user()->id;
            $replie['flg'] = '1';
            // 처리결과를 인서트
            $result = Replie::create($replie);
            // 인서트 정상시
            if($result){
                // 조회된 값의 어드민플레그 1로 변경
                $data->admin_flg = "1";
            }
            // 변경한 값 저장
            $data->save();
            // 저장
            DB::commit();
            // 메일발송 보내기
                Mail::send('mail.mail_answer', ['data' => $data], function($message) use ($data, $req){
                $message->to($req->email)->subject('이의이승페이지 문의내역');
                $message->from('dldmldltmd@gmail.com');
            });
            return response()->json([
                'code' => '0'
            ], 200);
        } catch(Exception $e){
            // 롤백
            DB::rollback();
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '댓글 달기 실패.'
            ], 400);
        }
    }
    // 게시글 삭제
    public function reportdel(Request $req){
        try {
            // 트랜잭션시작
            DB::beginTransaction();
            // 삭제처리
            if($req->flg==="1"){
                $data = Report::
                    where('b_id',$req->id)
                    ->where('flg',1)
                    ->first();
                $result = Replie::destroy($req->id);
            }else{
                $data = Report::
                    where('b_id',$req->id)
                    ->where('flg',0)
                    ->first();
                $result = Community::destroy($req->id);
            }
            // 조회된 값의 어드민플레그 1로 변경
            if(!($data->admin_flg === "3"||$data->admin_flg === "4")){
                $data->admin_flg = "1";
            }
            // 변경한 값 저장
            $data->save();
            // 저장
            DB::commit();
            return response()->json([
                'code' => '0'
            ], 200);
        } catch(Exception $e){
            // 롤백
            DB::rollback();
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '삭제 실패.'
            ], 400);
        }
    }
    // 게시글유지 플레그만 변경
    public function reportpost(Request $req){
        try {
            // 트랜잭션시작
            DB::beginTransaction();
            // 리퀘스트값으로 조회
            $data = Report::
                where('id',$req->id)
                ->first();
            // 플레그변경
            $data->admin_flg = $req->flg;
            // 변경한 값 저장
            $data->save();
            // 저장
            DB::commit();
            return response()->json([
                'code' => '0'
            ], 200);
        } catch(Exception $e){
            // 롤백
            DB::rollback();
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '변경실패 실패.'
            ], 400);
        }
    }
    // 유저조회
    public function userget(Request $req){
        $case = ['id','email'];
        $data = User::withTrashed()
            ->where($case[$req->flg],$req->val)
            ->orderby('id','desc')
            ->first();
        $result = Report::
            where('r_id',$data->id)
            ->orderby('restraint_at','desc')
            ->get();
        Log::debug($result);
        $data->cnt= $result->count();
        if($result->count() > 0){
            $data->res_at= $result->first()->restraint_at;
            $data->restraint= $result->first()->restraint;
        }
        if(empty($data)){
            return response()->json([
                'code' => '1',
                'errorMsg' => '조회된 회원이 없습니다'
            ], 200);
        }else if(!empty($data)){
            return response()->json([
                'code' => '0',
                'data' =>  $data,
            ], 200);
        }else{
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '회원조회에 실패했습니다'
            ], 400);
        }
    }
    // 유저제재
    public function restraintuser(Request $req){
        try {
            $date =[1,3,7,15,30,36500];
            // 트랜잭션시작
            DB::beginTransaction();
            if($req->id){
                $data = Report::
                    where('id',$req->id)
                    ->first();
                $data->r_id = $req->u_id;
                $data->restraint = $req->msg;
                if ($req->date === "5") {
                    $data->restraint_at = Carbon::parse('9999-01-01 00:00:00');
                }else{
                    $data->restraint_at = date("Y-m-d H:i:s", strtotime("+".$date[$req->date]." days"));
                }  
                $data->save();
            }else{
                // 인서트할 데이터 추가
                $data['r_id'] = $req->u_id;
                $data['restraint'] = $req->msg;
                if ($req->date === "5") {
                    $data['restraint_at'] = Carbon::parse('9999-01-01 00:00:00');
                }else{
                    $data['restraint_at'] = date("Y-m-d H:i:s", strtotime("+".$date[$req->date]." days"));
                }
                // 처리결과를 인서트
                $data = Report::create($data);
            }
            // 저장
            DB::commit();
            return response()->json([
                'code' => '0'
            ], 200);
        } catch(Exception $e){
            // 롤백
            DB::rollback();
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '변경 실패.'
            ], 400);
        }
    }
    // 유저제재해제
    public function restraintreset(Request $req){
        try {
            // 트랜잭션시작
            DB::beginTransaction();
            // 리퀘스트온 아이디가 있을때
            if($req->id){
                $data = Report::
                where('id',$req->id)
                ->first();
                $data['restraint_at'] = null;
                // 변경한 값 저장
                $data->save();
            }else{
                // 관리자가 제제해제 하는거라 모든 restraint_at null로 초기화
                $data = Report::
                where('r_id', $req->u_id)->update(['restraint_at' => null]);
            }
            // 저장
            DB::commit();
            return response()->json([
                'code' => '0'
            ], 200);
        } catch(Exception $e){
            // 롤백
            DB::rollback();
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '변경 실패.'
            ], 400);
        }
    }
    // 신고데이터 가져오기
    public function reportall(Request $req){
        $data = Report::
            select('users.email','reports.*')
            ->join('users', 'reports.u_id', '=', 'users.id')
            ->when($req->flg === "0", function ($query) {
                return $query->where('reports.admin_flg', "0");
            }, function ($query) {
                return $query->where('reports.admin_flg', '!=', "0");
            })
            ->orderby('reports.created_at','desc')
            ->paginate(8);
        Log::debug($data);
        return response()->json([
            'code' => '0',
            'data' => $data,
        ], 200);
    }
    
    // 삭제된게시물 복구
    public function repairpost(Request $req){
        try {
            // 트랜잭션시작
            DB::beginTransaction();
            if($req->flg==="0"){
                // 커뮤니티 게시글 조회
                $data = Community::withTrashed()
                    ->where('id',$req->id)
                    ->first();
                // 삭제일자 삭제
                $data->deleted_at = null;
                // 저장
                $data->save();
            }else if($req->flg==="1"){
                // 댓글 조회
                $data = Replie::withTrashed()
                    ->where('id',$req->id)
                    ->first();
                // 삭제일자 삭제
                $data->deleted_at = null;
                // 저장
                $data->save();
            }else{
                // 없을시
                return response()->json([
                    'code' => 'E99',
                    'errorMsg' => '변경 실패.'
                ], 400);
            }
            // 플래그변경위해 신고테이블조회
            $result = Report::
                where('b_id',$req->id)
                ->where('flg',$req->flg)
                ->first();
            if(!($result->admin_flg === "3"||$result->admin_flg === "4")){
                $result->admin_flg = '2';
            }
            // 변경한 값 저장
            $result->save();
            // 저장
            DB::commit();
            return response()->json([
                'code' => '0'
            ], 200);
        } catch(Exception $e){
            // 롤백
            DB::rollback();
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '변경 실패.'
            ], 400);
        }
    }
    // 건의 가져오기
    public function requestall(Request $req){
        if ($req->flg === "0") {
            $data = Community::
                select('users.email', 'community.*')
                ->where('community.admin_flg', "0")
                ->join('users', 'community.u_id', '=', 'users.id');
        } else {
            $data = Community::
                select('users.email', 'community.*', 'replies.replie')
                ->where('community.admin_flg', "1")
                ->join('replies', 'community.id', '=', 'replies.b_id')
                ->join('users', 'community.u_id', '=', 'users.id')
                ->where('replies.flg', "1");
        }
        $data = 
            $data
                ->where('community.flg', "3")
                ->orderBy('community.created_at', 'desc')
                ->paginate(8);
        return response()->json([
            'code' => '0',
            'data' => $data
        ], 200);
    }
    // 답변변경 변경
    public function answerupdate(Request $req){
        try {
            // 트랜잭션시작
            DB::beginTransaction();
            // 리퀘스트값으로 조회
            $data = Replie::
                where('b_id',$req->id)
                ->where('flg','1')
                ->first();
            // 플레그변경
            $data->replie = $req->replie;
            // 변경한 값 저장
            $data->save();
            // 저장
            DB::commit();
            return response()->json([
                'code' => '0'
            ], 200);
        } catch(Exception $e){
            // 롤백
            DB::rollback();
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '변경실패 실패.'
            ], 400);
        }
    }
    // 통계불러오기
    public function statistics(Request $req){
        // 좋아요 누른 성별수
        $gender = Like::
            select('users.gender', DB::raw('ROUND((COUNT(users.gender) / (SELECT COUNT(*) FROM likes WHERE flg = 0)) * 100, 2) as cnt'))
            ->join('users', 'likes.u_id', '=', 'users.id')
            ->where('likes.flg', '0')
            ->groupBy('users.gender')
            ->get();
            // 총 퍼센트 계산
            $total_gender = $gender->sum('cnt');     
            // 각 퍼센트 값을 전체에서의 비율로 변경
            $gender = $gender->map(function ($item) use ($total_gender) {
                $item->cnt = round(($item->cnt / $total_gender) * 100, 2);
                return $item;
            });
        // 플래그별 좋아요 눌린 비율
        $tag = Like::
            select(
                DB::raw("'couple_flg' as type"),
                DB::raw('sum(infos.couple_flg) as cnt')
            )
            ->join('infos', 'likes.b_id', '=', 'infos.id')
            ->where('likes.flg', '0')
            ->groupBy('type')
            ->union(
                Like::
                select(
                    DB::raw("'friend_flg' as type"),
                    DB::raw('sum(infos.friend_flg) as cnt')
                )
                ->join('infos', 'likes.b_id', '=', 'infos.id')
                ->where('likes.flg', '0')
                ->groupBy('type')
            )
            ->union(
                Like::
                select(
                    DB::raw("'family_flg' as type"),
                    DB::raw('sum(infos.family_flg) as cnt')
                )
                ->join('infos', 'likes.b_id', '=', 'infos.id')
                ->where('likes.flg', '0')
                ->groupBy('type')
            )
            ->get();
        
            // 총 퍼센트 계산
            $total = $tag->sum('cnt');
            
            // 각 퍼센트 값을 전체에서의 비율로 변경
            $tag = $tag->map(function ($item) use ($total) {
                $item->cnt = round(($item->cnt / $total) * 100, 2);
                return $item;
            });
        // 축제관광에서 좋아요 눌린 비율
        $main = Like::
            select(
                'infos.main_flg',
                DB::raw('ROUND((COUNT(infos.main_flg) / (SELECT COUNT(*) FROM likes WHERE flg = 0)) * 100, 2) as cnt')
            )
            ->join('infos', 'likes.b_id', '=', 'infos.id')
            ->groupBy('infos.main_flg')
            ->where('likes.flg', '0')
            ->get();
            // 총 퍼센트 계산
            $total = $main->sum('cnt');
            // 각 퍼센트 값을 전체에서의 비율로 변경
            $main = $main->map(function ($item) use ($total) {
                $item->cnt = round(($item->cnt / $total) * 100, 2);
                return $item;
            });
        // 나이대별 좋아요 누르는 비율
        $age = Like::
            select(DB::raw("COUNT(users.id) as cnt"), DB::raw("
            CASE 
                WHEN YEAR(CURDATE()) - YEAR(users.birthdate) BETWEEN 10 AND 19 THEN '10대'
                WHEN YEAR(CURDATE()) - YEAR(users.birthdate) BETWEEN 20 AND 29 THEN '20대'
                WHEN YEAR(CURDATE()) - YEAR(users.birthdate) BETWEEN 30 AND 39 THEN '30대'
                WHEN YEAR(CURDATE()) - YEAR(users.birthdate) BETWEEN 40 AND 49 THEN '40대'
                WHEN YEAR(CURDATE()) - YEAR(users.birthdate) >= 50 THEN '50대 이상'
                ELSE '기타' 
            END as age"))
            ->join('users', 'likes.u_id', '=', 'users.id')
            ->where('likes.flg', '0')
            ->groupBy('age')
            ->get();
        
            // 총 퍼센트 계산
            $total = $age->sum('cnt');
            
            // 각 퍼센트 값을 전체에서의 비율로 변경
            $age = $age->map(function ($item) use ($total) {
                $item->cnt = round(($item->cnt / $total) * 100, 2);
                return $item;
            });

        // 커뮤니티 좋아요 누른 성별수
        $gender1 = Like::
            select('users.gender', DB::raw('COUNT(users.gender) as cnt'))
            ->join('users', 'likes.u_id', '=', 'users.id')
            ->where('likes.flg','1')
            ->groupBy('users.gender')
            ->get();
            $total = $gender1->sum('cnt');
            // 각 퍼센트 값을 전체에서의 비율로 변경
            $gender1 = $gender1->map(function ($item) use ($total) {
                $item->cnt = round(($item->cnt / $total) * 100, 2);
                return $item;
            });
        // 커뮤니티 게시판 원하는정보 비율
        $main1 = Like::
            select(DB::raw('community.category_flg as type'), DB::raw('COUNT(community.category_flg) as cnt'))
            ->join('community', 'likes.b_id', '=', 'community.id')
            ->groupBy('community.category_flg')
            ->where('likes.flg','1')
            ->where('community.notice_flg','0')
            ->get();
            $total = $main1->sum('cnt');
            // 각 퍼센트 값을 전체에서의 비율로 변경
            $main1 = $main1->map(function ($item) use ($total) {
                $item->cnt = round(($item->cnt / $total) * 100, 2);
                return $item;
            });
        // 커뮤니티 게시판 원하는정보 비율
        $flg = Like::
            select(DB::raw('community.flg as type'), DB::raw('COUNT(community.flg) as cnt'))
            ->join('community', 'likes.b_id', '=', 'community.id')
            ->groupBy('community.flg')
            ->where('likes.flg','1')
            ->where('community.flg',"!=",'3')
            ->where('community.notice_flg','0')
            ->get();
            $total = $flg->sum('cnt');
            // 각 퍼센트 값을 전체에서의 비율로 변경
            $flg = $flg->map(function ($item) use ($total) {
                $item->cnt = round(($item->cnt / $total) * 100, 2);
                return $item;
            });
        // 여기서부터 조회수

        // 플래그별 평균 조회수
        $hit_flg = Info::
            select(
                DB::raw("'couple_flg' as type"),
                DB::raw('ROUND(AVG(infos.hits)) as cnt')
            )
            ->where('couple_flg','1')
            ->groupBy('type')
            ->union(
                Info::
                select(
                    DB::raw("'friend_flg' as type"),
                    DB::raw('ROUND(AVG(infos.hits)) as cnt')
                )
                ->where('friend_flg','1')
                ->groupBy('type')
            )
            ->union(
                Info::
                select(
                    DB::raw("'family_flg' as type"),
                    DB::raw('ROUND(AVG(infos.hits)) as cnt')
                )
                ->where('family_flg','1')
                ->groupBy('type')
            )
            ->get();

        // 축제 관광별 평균조회수
        $hit_main = Info::
            select(DB::raw('main_flg as type'), DB::raw('ROUND(AVG(infos.hits)) as cnt'))
            ->groupBy('main_flg')
            ->get();
        // 도별 평균 조회수
        $hit_ns = Info::
            select(DB::raw('ns_flg as type'), DB::raw('ROUND(AVG(infos.hits)) as cnt'))
            ->groupBy('ns_flg')
            ->get();
        // 가장 인기있는 도시 top5의 평균 조회수
        $hit_n = Info::
            select(DB::raw('states_name as type'), DB::raw('ROUND(AVG(infos.hits)) as cnt'))
            ->Where('ns_flg','경상북도')
            ->groupBy('states_name')
            ->orderBy('cnt','desc')
            ->limit(5)
            ->get();
        $hit_s = Info::
            select(DB::raw('states_name as type'), DB::raw('ROUND(AVG(infos.hits)) as cnt'))
            ->Where('ns_flg','경상남도')
            ->groupBy('states_name')
            ->orderBy('cnt','desc')
            ->limit(5)
            ->get();
        // 여기서 부터 유저
        // 유저성비
        $u_gender = User::withTrashed()
            ->select(
                DB::raw('CASE 
                            WHEN YEAR(CURDATE()) - YEAR(users.birthdate) BETWEEN 10 AND 19 THEN "10대"
                            WHEN YEAR(CURDATE()) - YEAR(users.birthdate) BETWEEN 20 AND 29 THEN "20대"
                            WHEN YEAR(CURDATE()) - YEAR(users.birthdate) BETWEEN 30 AND 39 THEN "30대"
                            WHEN YEAR(CURDATE()) - YEAR(users.birthdate) BETWEEN 40 AND 49 THEN "40대"
                            WHEN YEAR(CURDATE()) - YEAR(users.birthdate) >= 50 THEN "50대 이상"
                            ELSE "기타" 
                        END as age'),
                'gender as type',
                DB::raw('ROUND((COUNT(*) / (SELECT COUNT(*) FROM users)) * 100, 2) as per')
            )
            ->groupBy('age', 'gender')
            ->get();
        // 1달간 유저 가입탈퇴 동향
        $inout = User::
            select(DB::raw('COUNT(*) as cnt'))
            ->where('created_at', '>=', DB::raw('DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)'))
            ->union(
                User::withTrashed()
                ->select(DB::raw('COUNT(*) as cnt'))
                ->where('deleted_at', '>=', DB::raw('DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)'))
            )->get();
        // 회원 탈퇴한 사람들 사유비율
        $del_flg = Del_Reason::
            select(
                'del_titles.del_msg as type',
                DB::raw('ROUND((COUNT(*) / (SELECT COUNT(*) FROM del_reasons)) * 100, 2) as per')
            )
            ->join('del_titles','del_reasons.del_flg','del_titles.del_flg')
            ->groupBy('del_titles.del_msg')
            ->get();
        // 유저들 주 활동 시간대
        $u_time = Replie::withTrashed()
            ->select(
                DB::raw('CASE 
                            WHEN HOUR(created_at) >= 0 AND HOUR(created_at) < 6 THEN "새벽"
                            WHEN HOUR(created_at) >= 6 AND HOUR(created_at) < 12 THEN "오전"
                            WHEN HOUR(created_at) >= 12 AND HOUR(created_at) < 18 THEN "오후"
                            ELSE "저녁" 
                        END as time'),
                DB::raw('ROUND((COUNT(*) / (SELECT COUNT(*) FROM replies)) * 100, 2) as per')
            )
            ->groupBy('time')
            ->get();
        return response()->json([
            'code' => '0',
            'gender' =>  $gender,
            'gender1' =>  $gender1,
            'tag' =>  $tag,
            'flg' =>  $flg,
            'main' =>  $main,
            'main1' =>  $main1,
            'age' =>  $age,
            // 조회수영역
            'hit_flg' =>  $hit_flg,      
            'hit_main' =>  $hit_main,      
            'hit_ns' =>  $hit_ns,      
            'hit_n' =>  $hit_n,      
            'hit_s' =>  $hit_s,   
            // 유저영역   
            'u_gender' =>  $u_gender,   
            'inout' =>  $inout,   
            'del_flg' =>  $del_flg,   
            'u_time' =>  $u_time,   
        ], 200);
    }
    // 게시글 추가
    public function boardpost(Request $req){
        try {
            // 트랜잭션시작
            DB::beginTransaction();
            if($req->main_flg==="0"||$req->main_flg==="1"){
                // 기본 데이터
                $data = $req->only('title','content','ns_flg','states_name','place');
                // 변동데이터
                // 이미지
                if($req->img1==="undefined"&&$req->img2==="undefined"&&$req->img3==="undefined"){
                    $data['img1'] = '/img/default.png';
                }else if($req->img1!=="undefined"){
                    $imgName = Str::uuid().'.'.$req->img1->extension();
                    $req->img1->move(public_path('img'), $imgName);
                    $data['img1'] = '/img/'.$imgName;
                }else if($req->img2!=="undefined"){
                    $imgName = Str::uuid().'.'.$req->img2->extension();
                    $req->img2->move(public_path('img'), $imgName);
                    $data['img2'] = '/img/'.$imgName;
                }else if($req->img3!=="undefined"){
                    $imgName = Str::uuid().'.'.$req->img3->extension();
                    $req->img3->move(public_path('img'), $imgName);
                    $data['img3'] = '/img/'.$imgName;
                }
                // 플래그(커플친구가족주차)
                if($req->couple_flg){
                    $data['couple_flg']="1";
                }
                if($req->friend_flg){
                    $data['friend_flg']="1";
                }
                if($req->family_flg){
                    $data['family_flg']="1";
                }
                if($req->parking_flg){
                    $data['parking_flg']="1";
                }
                // 부가정보
                if($req->fee){
                    $data['fee'] = $req->fee;
                }
                if($req->time){
                    $data['time'] = $req->time;
                }
                if($req->holiday){
                    $data['holiday'] = $req->holiday;
                }
                if($req->tel){
                    $data['tel'] = $req->tel;
                }
                if($req->main_flg==="0"){
                    $data['start_at'] = $req->start_at;
                    $data['end_at'] = $req->end_at;
                    $data['main_flg'] = "축제";
                }else{
                    $data['main_flg'] = "관광";
                }
                // 축제테이블에 생성
                Info::create($data);
            }else{
                $data = $req->only('title','content');
                $data['u_id'] = Auth::user()->id;
                $data['category_flg'] = "2";
                $data['notice_flg'] = "1";
                if($req->img1!=="undefined"){
                    $imgName = Str::uuid().'.'.$req->img1->extension();
                    $req->img1->move(public_path('img'), $imgName);
                    $data['img1'] = '/img/'.$imgName;
                }else if($req->img2!=="undefined"){
                    $imgName = Str::uuid().'.'.$req->img2->extension();
                    $req->img2->move(public_path('img'), $imgName);
                    $data['img2'] = '/img/'.$imgName;
                }else if($req->img3!=="undefined"){
                    $imgName = Str::uuid().'.'.$req->img3->extension();
                    $req->img3->move(public_path('img'), $imgName);
                    $data['img3'] = '/img/'.$imgName;
                }
                if($req->main_flg==="2"){
                    $data['flg'] = "0";
                }
                if($req->main_flg==="3"){
                    $data['flg'] = "1";
                }
                if($req->main_flg==="4"){
                    $data['flg'] = "2";
                }
                if($req->main_flg==="5"){
                    $data['flg'] = "3";
                }
                // 커뮤니티에 생성
                Community::create($data);
            }
            // 저장
            DB::commit();
            return response()->json([
                'code' => '0'
            ], 200);
        } catch(Exception $e){
            // 롤백
            DB::rollback();
            return response()->json([
                'code' => 'E99',
                'errorMsg' => '변경실패 실패.'
            ], 400);
        }
    }
    // 전체보드조회
    public function boardget(Request $req){
        if ($req->flg === "0"&&empty($req->val)) {
            $result = Info::withTrashed()->select('id', 'u_id', 'created_at', 'title', 'content', 'main_flg as flg','deleted_at')
            ->union(Community::withTrashed()->select('id', 'u_id', 'created_at', 'title', 'content', 'flg','deleted_at'));
        } else if (in_array($req->flg, ["1", "2"])) {
            $result = Info::withTrashed()->select('id', 'u_id', 'created_at', 'title', 'content', 'main_flg as flg','deleted_at')
                ->where('main_flg', $req->flg === "1" ? '축제' : '관광');
        } else if (in_array($req->flg, ["3", "4", "5", "6"])) {
            $flgchange = ["3" => "0", "4" => "1", "5" => "2", "6" => "3"];
            $result = Community::withTrashed()->select('id', 'u_id', 'created_at', 'title', 'content', 'flg','deleted_at')
                ->where('flg', $flgchange[$req->flg]);
        // 전체 테이블에 조건 줄경우
        } else if (!empty($req->val)&&$req->flg === "0") {
            $data = Info::withTrashed()->select('id', 'u_id', 'created_at', 'title', 'content', 'main_flg as flg','deleted_at')
                ->when(!empty($req->val), function ($query) use ($req) {
                    if (in_array($req->sub_flg, ["0", "1"])) {
                        $subflgchange = ["0" => "id", "1" => "u_id"];
                        return $query->where($subflgchange[$req->sub_flg], $req->val);
                    } else if(in_array($req->sub_flg, ["2", "3"])){
                        $subflgchange1 = ["2" => "title", "3" => "content"];
                        return $query->where($subflgchange1[$req->sub_flg], 'LIKE', '%' . $req->val . '%');
                    }
                })->orderBy('created_at', 'desc')->get();
            $data1 = Community::withTrashed()->select('id', 'u_id', 'created_at', 'title', 'content', 'flg','deleted_at')
            ->when(!empty($req->val), function ($query) use ($req) {
                if (in_array($req->sub_flg, ["0", "1"])) {
                    $subflgchange = ["0" => "id", "1" => "u_id"];
                    return $query->where($subflgchange[$req->sub_flg], $req->val);
                } else if(in_array($req->sub_flg, ["2", "3"])){
                    $subflgchange1 = ["2" => "title", "3" => "content"];
                    return $query->where($subflgchange1[$req->sub_flg], 'LIKE', '%' . $req->val . '%');
                }
            })->orderBy('created_at', 'desc')->get();
            // 2개의 값을 합쳐 자체 페이지네이션
            $result = array_merge($data->toArray(), $data1->toArray());          
            $total = new LengthAwarePaginator(
                array_slice($result, ($req->page-1)*10, 10),
                count($result),
                10,
                Paginator::resolveCurrentPage(),
                ['path' => Paginator::resolveCurrentPath()]
            );  
            return response()->json([
                'code' => '0',
                'data' => $total
            ], 200);
        }
        $result = $result
        ->when(!empty($req->val), function ($query) use ($req) {
            if (in_array($req->sub_flg, ["0", "1"])) {
                $subflgchange = ["0" => "id", "1" => "u_id"];
                return $query->where($subflgchange[$req->sub_flg],$req->val);
            } else if(in_array($req->sub_flg, ["2", "3"])){
                $subflgchange1 = ["2" => "title", "3" => "content"];
                return $query->where($subflgchange1[$req->sub_flg], 'LIKE', '%' . $req->val . '%');
            }
        })
        ->orderBy('created_at', 'desc')->paginate(10);
        Log::debug($result);
        return response()->json([
            'code' => '0',
            'data' => $result
        ], 200);
    }
    // 전체댓글조회
    public function replieget(Request $req){
        $result = Replie::withTrashed()->select('id', 'u_id', 'b_id', 'replie', 'flg', 'deleted_at', 'created_at')
        ->when(in_array($req->flg, ["1"]), function ($query) use ($req) {
            return $query->where('flg', $req->flg === "0");
        })
        ->when(in_array($req->flg, ["2"]), function ($query) use ($req) {
            return $query->where('flg', $req->flg === 1); // 원하는 처리 추가
        })
        ->when(!empty($req->val), function ($query) use ($req) {
            if (in_array($req->sub_flg, ["0", "1", "2"])) {
                $subflgchange = ["0" => "id", "1" => "u_id", "2" => "b_id"];
                return $query->where($subflgchange[$req->sub_flg], $req->val);
            } else if ($req->sub_flg === "3") {  
                return $query->where('replie', 'LIKE', '%' . $req->val . '%');
            }
        })
        ->orderBy('created_at', 'desc')->paginate(10);
        return response()->json([
            'code' => '0',
            'data' => $result
        ], 200);
    }
    // 특정시간에 동작
    public function test(Request $req){
        $schedule->call(function () {
            // 여기에 실행할 쿼리문을 작성
            \DB::statement('YOUR SQL QUERY HERE');
        })->when(function () {
            // 특정 날짜와 시간에만 실행되도록 설정
            $targetDateTime = '2024-01-15 15:30:00'; // 예시: 2024년 1월 15일 오후 3시 30분
            return now()->toDateTimeString() === $targetDateTime;
        });
    }
    
}