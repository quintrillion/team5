<template>
	<div class="detail_frame">
		<div class="detail_container">
			<div class="detail_header_flex">
				<div v-if="this.detaildata.flg === '0'" class="detail_type font_air bold center">
					자유게시판
				</div>
				<div v-else="this.detaildata.flg === '1'" class="detail_type font_air bold center">
					정보게시판
				</div>
				<div class="detail_header font_air bold">
					<div class="detail_title  center">
						{{this.detaildata.title}}
					</div>
				</div>
			</div>
			<div class="detail_flex">
				<div>
					<span class="font_air bold detail_hits community_margin" v-if="detaildata.notice_flg === '0'">{{this.detaildata.nick}}</span>
					<span class="font_air bold detail_hits community_margin" v-if="detaildata.notice_flg === '1'">관리자</span>
					<span class="font_air bold detail_com_tofrom community_margin">|</span>
					<span class="font_air bold detail_hits community_margin">{{formatEventDate(this.detaildata.created_at)}}</span>
					<span class="font_air bold detail_hits community_margin" v-if="!(this.detaildata.created_at === this.detaildata.updated_at)">
						(수정됨)
					</span>
				</div>
				<div class="font_air bold detail_hits">
					조회수 : {{this.detaildata.hits}}
				</div>
			</div>
			<div class="detail_body">
				<div class="detail_img"
					v-if="this.detaildata.img1||this.detaildata.img2||this.detaildata.img3"
				>
					<img :src="this.detaildata.img1"
						v-if="this.detaildata.img1"
					>
					<br>
					<img :src="this.detaildata.img2"
						v-if="this.detaildata.img2"
					>
					<br>
					<img :src="this.detaildata.img3"
						v-if="this.detaildata.img3"
					>
				</div>
				<div class="detail_content font_air bold">
					<span class="detail_content font_air bold" v-text="this.content"></span>
				</div>
			</div>
			<div class="detail_post_like d-flex justify-content-between">
				<div class="detail_content community_margin community_heart font_air bold pointer" @click="plusheart" >
					<span class="community_like font_air bold" :class="this.likeflg ? 'community_heart_red' : 'community_heart_black'"><font-awesome-icon :icon="['fas', 'heart']"/></span>
					<span class="community_like font_air bold">좋아요</span>
					<span class="community_like font_air bold">{{this.detaildata.cnt}}</span>
				</div>
				<!-- 게시글 신고 모달 -->
				<div class="modal reportModal" tabindex="-1" id="reportmodal">
					<div class="modal-dialog modal-dialog-centered">
						<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title qna_update">게시글 신고</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<div class="qna_report">
								<div class="qna_report_margin">
									<span class="qna_report_con">제목 : </span>
									<span class="font_air bold detail_com_tofrom qna_report_content">{{ this.detaildata.title }}</span>
								</div>
								<div class="qna_report_margin">
									<span class="qna_report_con">작성자 : </span>
									<span class="font_air bold detail_com_tofrom qna_report_content">{{ this.detaildata.nick }}</span>
								</div>
								<span class="qna_report_con">신고사유</span>
								<input type="text" id="titleInput" style="height: 5rem" class="form-control qna_tit" v-model="reportmsg">
							</div>
						</div>
						<div class="modal-footer d-flex justify-content-center">
							<button type="button" class="btn btn-primary qna_modal_btn qna_color" @click="reportPost('0',this.b_id)">신고완료</button>
							<button type="button" class="btn btn-light qna_modal_btn" data-bs-dismiss="modal">닫기</button>
						</div>
						</div>
					</div>
				</div>
				<!-- 수정 모달 -->
				<div class="modal" tabindex="-1"  id="updatemodal">
					<div class="modal-dialog modal-dialog-centered">
						<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title qna_update">게시글 수정</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<div class="detail_type font_air bold qna_select">
								<div>
									<span>게시판?</span>
									<select v-model="editedFlg" name="flg" class="form-select qna_drop" aria-label=".form-select-sm">
										<option v-for="flgOption in flgOptions" :key="flgOption.value" :value="flgOption.value" class="qna_drop_item font_air bold">{{ flgOption.label }}</option>
									</select>
								</div>
								<div>
									<span>카테고리?</span>
									<select v-model="editedCategory" name="category_flg" class="form-select qna_drop" aria-label=".form-select-sm">
										<option v-for="categoryOption in categoryOptions" :key="categoryOption.value" :value="categoryOption.value" class="qna_drop_item">{{ categoryOption.label }}</option>
									</select>
								</div>
							</div>
							<br>
							<div class="qna_tit">
								<span class="detail_type">제목</span>
								<input v-model="editedTitle" type="text" id="titleInput" class="form-control qna_tit">
								<span class="detail_type">내용</span>
								<textarea v-model="editedContent" id="contentInput" style="height: 10rem" class="form-control"></textarea>
							</div>
							<!-- 사진 업로드 -->
							<label for="formFile" class="form-label detail_type">사진 확인</label>
							<div class="input-group">
								<span class="input-group-text font_air bold" >사진</span>
								<input id="modal_file_img1_text" class="form-control font_air bold" type="text" readOnly :value="this.detaildata.img1">
								<button type="button" class="btn btn-secondary font_air bold" @click="this.detaildata.img1 = ''">삭제</button>
							</div>
							<div class="input-group">
								<span class="input-group-text font_air bold" >사진</span>
								<input id="modal_file_img2_text" class="form-control font_air bold" type="text" readOnly :value="this.detaildata.img2">
								<button type="button" class="btn btn-secondary font_air bold" @click="this.detaildata.img2 = ''">삭제</button>
							</div>
							<div class="input-group">
								<span class="input-group-text font_air bold" >사진</span>
								<input id="modal_file_img3_text" class="form-control font_air bold" type="text" readOnly :value="this.detaildata.img3">
								<button type="button" class="btn btn-secondary font_air bold" @click="this.detaildata.img3 = ''">삭제</button>
							</div>
							<label for="formFile" class="form-label detail_type detail_margin">사진 변경</label>
							<div class="input-group">
								<input id="modal_file_img1" class="form-control font_air bold" type="file" accept="image/*">
							</div>
							<div class="input-group">
								<input id="modal_file_img2" class="form-control font_air bold" type="file" accept="image/*">
							</div>
							<div class="input-group">
								<input id="modal_file_img3" class="form-control font_air bold" type="file" accept="image/*">
							</div>
						</div>
						<div class="modal-footer d-flex justify-content-center">
							<button type="button" class="btn btn-light qna_modal_btn" data-bs-dismiss="modal">닫기</button>
							<button type="button" class="btn btn-primary qna_modal_btn qna_color" @click="detailedit()">수정완료</button>
						</div>
						</div>
					</div>
				</div>
				<div class="post_btn_bot">
					<div class="qna_report_open">
						<button type="button" v-if="!(this.detaildata.u_id === this.userauth)" id="openModalBtn" data-bs-toggle="modal" data-bs-target="#reportmodal" @click="reportmodal">신고</button>
					</div>
					<button type="button" v-if="this.detaildata.u_id === this.userauth" data-bs-toggle="modal" data-bs-target="#updatemodal" @click="editbefore">수정</button>
					<button type="button" @click="goBack">목록</button>
					<button type="button" v-if="this.detaildata.u_id === this.userauth" @click="delpost()">삭제</button>
				</div>
			</div>
		</div>
		<div class="detail_replie_container">
			<div class="detail_replie_write_box font_air bold">
				<div class="font_air bold">
					댓글쓰기  ({{ this.repliecount }})
				</div>
				<div class="detail_replie_relative">	
					<textarea type="text" maxlength="200"
						v-model="replie"  
						@click="checklocal"
						@input="changeKeyword"
						@keyup.enter="repliewrite"
						placeholder="이곳에 소중한 댓글을 작성해 주세요."
					></textarea>
					<div class="detail_replie_length font_air bold">
						{{ this.replie_length }}/200
					</div>
					<button
						@click="repliewrite"
						class="detail_replie_write_box_btn pointer"
					><font-awesome-icon :icon="['fas', 'pen-to-square']" /></button>
				</div>
			</div>
			<!-- 댓글 조회 -->
			<div class="detail_replie_read" 
				v-if="repliedata.length !== 0"
				v-for="data in repliedata" :key="data"
				:id='"detail_replie"+data.id'
				>
				<div class="detail_replie_flex">
					<div class="font_air bold detail_replie_nick">
						{{ data.nick }}
						<span class="font_air bold">
							({{ masking(data.email) }})
						</span>
					</div>
					<div class="font_air bold detail_replie_created_at">
						{{ converttime(data.created_at) }}
					</div>
				</div>
				<div class="font_air bold detail_replie_content">
					{{ data.replie }}
				</div>
				<div class="detail_replie_change"
					v-if="checkUser(data.email)"
				>
					<div class="font_air bold pointer"
						@click="del_replie(data.id)"
					>
						삭제
					</div>
				</div>
				<div class="detail_replie_change" v-else>
					<!-- 댓글 신고 모달 -->
					<div class="modal reportrepliemodal" tabindex="-1" id="reportrepliemodal">
						<div class="modal-dialog modal-dialog-centered">
							<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title qna_update">댓글 신고</h5>
								<button type="button" class="btn-close btn-close1" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<div class="qna_report" id="qna_replie">
									<div class="qna_report_margin" id="qna_replie">
										<span class="qna_report_con">댓글 : </span>
										<span class="font_air bold detail_com_tofrom qna_report_content">{{ data.replie }}</span>
									</div>
									<div class="qna_report_margin" id="qna_replie">
										<span class="qna_report_con">작성자 : </span>
										<span class="font_air bold detail_com_tofrom qna_report_content">{{ data.nick }}</span>
									</div>
									<span class="qna_report_con">신고사유</span>
									<input type="text" id="titleInput" style="height: 5rem" class="form-control qna_tit" v-model="reportmsg">
								</div>
							</div>
							<div class="modal-footer d-flex justify-content-center">
								<button type="button" class="btn btn-primary qna_modal_btn qna_color" @click="reportPost('1',data.id)">신고완료</button>
								<button type="button" class="btn btn-light qna_modal_btn" data-bs-dismiss="modal">닫기</button>
							</div>
							</div>
						</div>
					</div>
					<div class="font_air bold pointer"
						data-bs-toggle="modal" data-bs-target="#reportrepliemodal"
						@click="reportrepliemodal"
					>
						<span class="font_air bold">신고</span>
					</div>
				</div>
			</div>
			<div class="detail_replie_read" 
				v-if="repliedata.length === 0">
				<div class="font_air bold detail_no_replie">
					작성된 댓글이 없습니다.
				</div>
			</div>
		</div>
		<div class="center detail_more_btn">
			<span class="center pointer font_air bold"
				@click="morereplie"
				v-if="!(moreflg)&& this.repliecount > 20"
			>
				더보기
				<br>
				<font-awesome-icon :icon="['fass', 'caret-down']" />
			</span>
		</div>
		<div class="goingTop" onclick="window.scrollTo(0,0);"><font-awesome-icon :icon="['fas', 'chevron-up']" /></div>
	</div>
</template>
<script>
import axios from 'axios';
import Swal from 'sweetalert2';
export default {
	name: 'DetailComponent',

	data() {
		return {
			detaildata: [],
			content: "",
			repliedata: [],
			replie: "",
			replie_length: 0,
			b_id: "",
			repliecount: "",
			new_replie: "",
			replie_offset: 20,
			moreflg: false,
			userauth: "",
			likeflg: false,
			editedTitle: '',
			editedContent: '',
			editedFlg: '',
			editedCategory: '',
			// 신고 기능
			report: '',
			reportdata: '',
			reportmsg: '',
			flgOptions: [
				{ label: '자유', value: '0' },
				{ label: '정보', value: '1' },
				{ label: '질문', value: '2' },
				{ label: '건의', value: '3' }
			],
			categoryOptions: [
				{ label: '축제', value: '0' },
				{ label: '관광', value: '1' },
				{ label: '기타', value: '2' }
			],
		}
	},
	watch: {
		replie(){
			this.replieLength();
		},
	},
	created() {
		// 로컬스토리지에 저장된 정보있는지 확인
		let boo = localStorage.getItem('nick') ?  true : false;
		this.$store.commit('setLocalFlg', boo);

		// 현재url가져오기
		let params = new URLSearchParams(window.location.search);
		this.b_id = params.get('id');
		this.getinfo(this.b_id);
	},

	updated() {
	},
	methods: {
		// 게시글 정보 조회
		getinfo(){
			// 스피너 로딩바
			this.$store.commit('setLoading',true);
			const URL = '/community/info?id='+this.b_id;
			axios.get(URL)
			.then(res => {
				if(res.data.code==="0"){
					this.detaildata = res.data.data[0];
					this.repliedata = res.data.replie;
					this.repliecount = res.data.repliecount;
					this.userauth = res.data.userauth;
					this.likeflg = res.data.likeresult;
					this.content = res.data.data[0].content;
				}else if(res.data.code==="E99"){
					Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.data.errmsg,
                    confirmButtonText: '확인'
                })
				}
			})
			.catch(err => {
				Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '에러가 발생했습니다',
                    confirmButtonText: '확인'
                })
			})
			.finally(() => {
                this.$store.commit('setLoading', false);
            });
		},
		// 댓글작성
		repliewrite(){
			if(!(localStorage.getItem('nick'))){
                Swal.fire({
                    icon: 'warning',
                    title: '주의',
                    text: '로그인 후 이용해 주세요.',
                    showCancelButton: true,
                    confirmButtonText: '확인',
                    cancelButtonText: '취소',
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        this.$router.push('/login')
                    }
                })
            }else{
				if(this.replie){
					const URL = '/community/reply/'+this.b_id;
					const formData = new FormData();
					formData.append('replie', this.replie);
					formData.append('b_id', this.b_id);
					formData.append('flg', '1');
					axios.post(URL,formData)
					.then(res =>{
						if(res.data.code==="0"){
							this.replie = "";
							this.repliecount++;
							this.repliedata.unshift(res.data.data);
						}else{
							Swal.fire({
							icon: 'error',
							title: 'Error',
							text: res.data.errorMsg,
							confirmButtonText: '확인'
					})
						}
					})
					.catch(err => {
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: err.response.data.errorMsg,
							confirmButtonText: '확인'
						})
					})
				}else{
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: '댓글을 작성해 주세요.',
						confirmButtonText: '확인'
					})
				}		
			}
		},
		// 좋아요 입력
		plusheart() {
			if(!(this.userauth === "")) {				
				// 현재url가져오기
				let params = new URLSearchParams(window.location.search);
				this.b_id = params.get('id');
				const URL = '/community/heartpost';
				axios.post(URL, {
					"b_id": this.b_id,
					"flg": "1"
				})
				.then(res=>{
					if(res.data.code==="0"){
						this.likeflg = res.data.likeflg;
						if(res.data.likeflg===false&&this.detaildata.cnt > 0) {
							this.$router.push('/community?id='+this.b_id);
							this.detaildata.cnt--;
						} else {
							this.$router.push('/community?id='+this.b_id);
							this.detaildata.cnt++;
						}
					}
				})
				.catch(err => {
					console.log("plusheart 함수 catch");
					// this.$router.push('/error');
				})				
			} else {
				Swal.fire({
					icon: 'warning',
					title: '주의',
					text: '로그인 후 이용해 주세요.',
					showCancelButton: true,
					confirmButtonText: '확인',
					cancelButtonText: '취소',
                })
				.then((result) => {
					if (result.isConfirmed) {
						this.$router.push('/login');
					}
				});
			}
		},
		// 댓글삭제
		del_replie(id){
			Swal.fire({
                icon: 'warning',
                title: '주의',
                text: '댓글을 삭제하시겠습니까?.',
                showCancelButton: true,
                confirmButtonText: '확인',
                cancelButtonText: '취소',
            })
            .then((result) => {
                if (result.isConfirmed) {
					const URL = '/community/del/'+id;
                    const formData = new FormData();
                    axios.post(URL,formData)
                    .then(res =>{
                        if(res.data.code==="0"){
                            document.querySelector('#detail_replie'+id).remove();
                            this.repliecount--;
							if(this.repliecount === 0){
								this.getinfo();
							}
                        }else{
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.data.errorMsg,
                                confirmButtonText: '확인'
                            })
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.errorMsg,
                            confirmButtonText: '확인'
                        })
                    })
                }
            })
		},
		// 댓글추가 불러오기
		morereplie(){
			const URL = '/community/more/?b_id='+this.b_id+'&offset='+this.replie_offset;
			axios.get(URL)
			.then(res =>{
				if(res.data.code==="0"){
					this.repliedata = [ ...this.repliedata, ...res.data.data ];
					this.replie_offset = this.replie_offset+20;			
					if(this.repliedata.length === this.repliecount||res.data.data.length<20){
						this.moreflg = true;
					}
				}else{
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: res.data.errorMsg,
						confirmButtonText: '확인'
				})
				}
			})
			.catch(err => {
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: err.response.data.errorMsg,
						confirmButtonText: '확인'
				})
			})		
		},
		// 게시물 삭제
		delpost(){	
			Swal.fire({
			title: '삭제 확인',
			text: '정말로 삭제하시겠습니까?',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#d33',
			cancelButtonColor: '#3085d6',
			confirmButtonText: '삭제',
			cancelButtonText: '취소'
		}).then((result) => {
			if (result.isConfirmed) {
				// 사용자가 확인을 눌렀을 때의 처리
				this.$store.commit('setLoading', true);
				let params = new URLSearchParams(window.location.search);
				this.b_id = params.get('id');
				const URL = '/community/delete?id=' + this.b_id;
				axios.delete(URL)
					.then(res => {
						if (res.data.code === "0") {
							Swal.fire({
								icon: 'success',
								title: '완료',
								text: '정상처리되었습니다.',
								confirmButtonText: '확인'
							});
							this.goBack();
						} 
						
					})
					.catch(err => {
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: '에러발생.',
							confirmButtonText: '확인'
						});
					})
					.finally(() => {
						this.$store.commit('setLoading', false);
					});
				}
			});
		},
		// 게시글 수정요소 정의
		editbefore() {
			console.log("함수진입");
			this.editedTitle = this.detaildata.title;
			this.editedContent = this.detaildata.content;
			this.editedFlg = this.detaildata.flg;
			this.editedCategory = this.detaildata.category_flg;
		},
		// 게시글 수정
		detailedit() {
			const URL = '/community/update?id=' + this.b_id;
			let img1 = document.querySelector('#modal_file_img1');
			let img2 = document.querySelector('#modal_file_img2');
			let img3 = document.querySelector('#modal_file_img3');
			let b_img1 = document.querySelector('#modal_file_img1_text');
			let b_img2 = document.querySelector('#modal_file_img2_text');
			let b_img3 = document.querySelector('#modal_file_img3_text');
			console.log(this.editedTitle)
			console.log(this.editedContent)
			console.log(this.editedFlg)
			console.log(this.editedCategory)
			const formData = new FormData();
			formData.append('title', this.editedTitle);
			formData.append('content', this.editedContent);
			formData.append('flg', this.editedFlg);
			formData.append('category_flg', this.editedCategory);
			formData.append('img1', img1.files[0]);
			formData.append('img2', img2.files[0]);
			formData.append('img3', img3.files[0]);
			formData.append('b_img1', b_img1.value);
			formData.append('b_img2', b_img2.value);
			formData.append('b_img3', b_img3.value);
			axios.post(URL, formData)
			.then(res => {
				if(res.data.code === "0") {
					Swal.fire({
						icon: 'success',
						title: '완료',
						text: '게시글이 수정되었습니다.',
						confirmButtonText: '확인'
					}).then(() => {
						// 확인 버튼을 눌렀을 때 페이지를 리로드합니다.
						location.reload();
					});
				}
			})
			.catch(err => {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: '에러가 발생했습니다',
					confirmButtonText: '확인'
				});
			});
		},
		// created_at 데이터 출력 변환
		formatEventDate(dateString) {
			const dateObject = new Date(dateString);
			const year = dateObject.getFullYear();
			const month = String(dateObject.getMonth() + 1).padStart(2, "0");
			const day = String(dateObject.getDate()).padStart(2, "0");

			return `${year}-${month}-${day}`;
		},
		// 시간초기화
		converttime(date){
			const start = new Date(date);
			const end = new Date();
			const diff = (end - start) / 1000;
			const times = [
				{ name: '년', milliSeconds: 60 * 60 * 24 * 365 },
				{ name: '개월', milliSeconds: 60 * 60 * 24 * 30 },
				{ name: '일', milliSeconds: 60 * 60 * 24 },
				{ name: '시간', milliSeconds: 60 * 60 },
				{ name: '분', milliSeconds: 60 },
			];
			for (const value of times) {
				const betweenTime = Math.floor(diff / value.milliSeconds);
				if (betweenTime > 0) {
				return `${betweenTime}${value.name} 전`;
				}
			}
			return '방금 전';
		},
		// 몇글자 적은지 확인
		replieLength() {
			this.replie_length = this.replie.length;
		},
		// 한글 바로입력
		changeKeyword(e) {
			this.replie = e.target.value;
		},
		// 로그인확인
		checklocal() {
			if(!(localStorage.getItem('nick'))){
                Swal.fire({
                    icon: 'warning',
                    title: '주의',
                    text: '로그인 후 이용해 주세요.',
                    showCancelButton: true,
                    confirmButtonText: '확인',
                    cancelButtonText: '취소',
                })
                .then((result) => {
                    if (result.isConfirmed) {
                        this.$router.push('/login')
                    }
                })
            }
		},
		// 유저 본인인지 확인
		checkUser(email) {
			return email === localStorage.getItem('email');
		},
		// 이메일 마스킹
		masking(str) {
			// 전달받은 값이 문제가있는지 확인하는 함수
			const testText = (text) => {
				return typeof text === 'undefined' || text === null || text === '';
			};
			// 전달받은 값 변수에 저장
			let teststr = str;
			// 전달받은값 이메일 형식인지 확인
			let result = teststr.match(/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/);
			// 공변수 셋팅
			let strLength;
			// 전달받은 값이 문제가 있었거나 정규식 한 값이 잘못됬을경우 
			if (testText(teststr) || testText(result)) {
				// 기존값 리턴
				return teststr;
			} else {
				// 전달받은값의 @전의길이 - 5한 값
				strLength = result.toString().split('@')[0].length - 5;
				// 전달받은 값의 @앞 부분내용중 @ 앞에 있는 부분의 4글자 만큼 *로 변경
				return teststr.toString().replace(new RegExp('.(?=.{0,' + strLength + '}@)', 'g'), '*');
			}
		},	
		// 뒤로가기 동작 실행
		goBack() {
			this.$router.go(-1);
		},
		// 신고 기능
		reportPost(flg,id) {
			if(this.reportmsg === ""){
				Swal.fire({
					icon: 'warning',
					title: '주의',
					text: '신고사유를 입력해 주세요',
					confirmButtonText: '확인'
				})
			}else{	
				let tt = ['게시글','댓글']
				const URL = '/post/re';
				const formData = new FormData();
				formData.append('b_id',id);
				formData.append('flg', flg);
				formData.append('content', this.reportmsg);
				axios.post(URL,formData)
				.then(res =>{
					if(res.data.code==="0"){
						Swal.fire({
							icon: 'success',
							title: '완료',
							text: '정상처리되었습니다.',
							confirmButtonText: '확인'
						})
					}else if(res.data.code==="1"){
						Swal.fire({
							icon: 'warning',
							title: '주의',
							text: '이미 신고하신 '+tt[flg]+" 입니다.",
							confirmButtonText: '확인'
						})
					}
				})
				.catch(err => {
					document.querySelector('.btn-close').click();
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: err.response.data.errorMsg,
						confirmButtonText: '확인'
					})
				})
				.finally(() => {
					document.querySelector('.btn-close').click();
					document.querySelector('.btn-close1').click();
					this.reportmsg = "";
				});
			}
		},
		// 모달클릭시 플래그
		reportmodal(){
			if(!(localStorage.getItem('nick'))){
				document.querySelector('.btn-close').click();
				Swal.fire({
                    icon: 'warning',
                    title: '주의',
                    text: '로그인 후 이용해 주세요.',
					showCancelButton: true,
					confirmButtonText: '확인',
					cancelButtonText: '취소',
				})
				.then((result) => {
					if (result.isConfirmed) {
						this.$router.push('/login')
					}
				})
			}
		},
		reportrepliemodal(){
			if(!(localStorage.getItem('nick'))){
				document.querySelector('.btn-close').click();
				Swal.fire({
                    icon: 'warning',
                    title: '주의',
                    text: '로그인 후 이용해 주세요.',
					showCancelButton: true,
					confirmButtonText: '확인',
					cancelButtonText: '취소',
				})
				.then((result) => {
					if (result.isConfirmed) {
						this.$router.push('/login')
					}
				})
			}
		},
	},
	beforeRouteLeave(to, from, next) {
		next();
	},

}
</script>