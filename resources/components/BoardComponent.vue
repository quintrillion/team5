<template>
	<div class="qna_frame">
		<div class="qna_container">
			<div class="qna_header">
				<h1 v-if="this.nowflg==='0'">자유게시판</h1>
				<h1 v-else="this.nowflg==='1'">정보게시판</h1>
				<div class="qna_header_bot">
					<div class="qna_header_l">
						<select @change="formSelect($event)" class="form-select qna_drop my-3" aria-label=".form-select-sm">
							<option selected value="0" class="qna_drop_item">전체</option>
							<option value="1" class="qna_drop_item">축제</option>
							<option value="2" class="qna_drop_item">관광</option>
							<option value="3" class="qna_drop_item">기타</option>
						</select>
						<!-- 클릭시 버튼 동그라미 색상 변경 #D14C6C/ 글자 검정색/ 좀만 크게 -->
						<div class="qna_btn">
							<div class="btn-group" role="group">
								<button type="button" class="btn">
									<span class="font_center" ><font-awesome-icon :icon="['fas', 'circle']"/></span>최신순
								</button>
								<button type="button" class="btn">
									<span class="font_center"><font-awesome-icon :icon="['fas', 'circle']"/></span>조회순
								</button>
								<button type="button" class="btn">
									<span class="font_center"><font-awesome-icon :icon="['fas', 'circle']"/></span>좋아요순
								</button>
							</div>
						</div>
					</div>
					<!-- 반응형 숨기기 -->
					<div class="qna_header_r">
						<div>
							총 <span class="qna_pink"> {{ cntinfo }}</span>건의 게시글이 있습니다.
						</div>
					</div>
				</div>
			</div>	
			<div class="info_list" >
				<table>
					<colgroup>
						<col class="info_id col_hidden">
						<col class="info_category">
						<col class="info_title">
						<col class="info_writer">
						<col class="info_create_at">
						<col class="info_hits col_hidden">
						<col class="info_likes col_hidden">
					</colgroup>
					<thead>
						<tr>
							<th class="col_hidden">번호</th>
							<th>카테고리</th>
							<th>제목</th>
							<th>작성자</th>
							<th>작성일자</th>
							<th class="col_hidden">조회수</th>
							<th class="col_hidden">좋아요수</th>
						</tr>
					</thead>
					<tbody v-for="infodata in infolist" :key="infodata">
						<!-- <tr class="info_notice pointer">
							<td class="col_hidden">23</td>
							<td>공지사항</td>
							<router-link to="/community">
								<td class="info_title">정보게시판 글 작성시 양식을 지켜주세요!</td>
							</router-link>
							<td>운영자</td>
							<td>23-12-25</td>
							<td class="col_hidden">401</td>
							<td class="col_hidden">25</td>
						</tr> -->
						<tr class="pointer">
							<td class="col_hidden">{{ infodata.id }}</td>
							<td>{{ infodata.category_flg }}</td>
							<td class="info_title">{{ infodata.title }}</td>
							<td>{{ infodata.nick }}</td>
							<td>{{ infodata.created_at }}</td>
							<td class="col_hidden">{{ infodata.hits }}</td>
							<td class="col_hidden">{{ infodata.cnt }}</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="qna_btn_bot d-flex flex-row-reverse mt-5 mb-5">
				<button type="button">글 작성하기</button>
			</div>
				<div>
					<nav aria-label="Page navigation">
						<ul class="pagination justify-content-center qna_pagin">
							<li class="page-item">
								<a
									id="qna_font"
									class="page-link"
									href="#"
									aria-label="Previous"
								>
									<span aria-hidden="true"
										><font-awesome-icon :icon="['fas', 'angle-left']"
									/></span>
								</a>
							</li>
							<li class="page-item">
								<a
									id="qna_font"
									class="page-link"
									href="#"
									aria-label="Previous"
								>
									<span aria-hidden="true"
										><font-awesome-icon :icon="['fas', 'angles-left']"
									/></span>
								</a>
							</li>
							<li class="page-item">
								<a id="qna_font" class="page-link" href="#">1</a>
							</li>
							<li class="page-item">
								<a id="qna_font" class="page-link" href="#">2</a>
							</li>
							<li class="page-item">
								<a id="qna_font" class="page-link" href="#">3</a>
							</li>
							<li class="page-item">
								<a id="qna_font" class="page-link" href="#">4</a>
							</li>
							<li class="page-item">
								<a id="qna_font" class="page-link" href="#">5</a>
							</li>
							<li class="page-item">
								<a id="qna_font" class="page-link" href="#" aria-label="Next">
									<span aria-hidden="true"
										><font-awesome-icon :icon="['fas', 'angle-right']"
									/></span>
								</a>
							</li>
							<li class="page-item">
								<a id="qna_font" class="page-link" href="#" aria-label="Next">
									<span aria-hidden="true"
										><font-awesome-icon :icon="['fas', 'angles-right']"
									/></span>
								</a>
							</li>
						</ul>
					</nav>
				</div>
		</div>
	</div>
</template>
<script>
export default {
	name: "BoardComponent",

	data() {
		return {
			infolist: [],
			nowflg: "",
			cntinfo: 0,
			formSelectData: 0,
		}
	},
	created() {
		const objUrlParam = new URLSearchParams(window.location.search);
		this.nowflg = objUrlParam.get('flg');
		console.log(this.nowflg )
		this.getInfo( this.nowflg );
		this.getCategoryInfo( this.nowflg );
	},
	beforeRouteUpdate() {
		// url의 파라미터를 가져옴
		const objUrlParam = new URLSearchParams(window.location.search);
		this.nowflg = objUrlParam.get('flg')==="0"? "1":"0";
		this.getInfo( this.nowflg, this.formSelectData );
	},
	mounted() {
	},
	methods: {
		// 해당 게시판의 모든 게시글 조회
		getInfo(flg, data) {
			console.log(data);
			// 해당url의 데이터 가져오기
			const URL = '/board/info?flg='+ flg + '&category=' + this.formSelectData;
			console.log("getinfo 함수진입")
			// axios는 http status code가 200번대면 then으로, 그외에는 catch로
			axios.get(URL)
			.then(res => {
				console.log("then");
				console.log("레스데이터"+res.data);
				if(res.data.code === '0') {
					this.infolist = res.data.information;
					this.cntinfo = res.data.infocnt;
				}
				console.log('nowflg='+this.nowflg )
			})
			.catch(err => {
        		this.$router.push('/error');
			})
		},

		formSelect(event) {
			console.log(event.target.value);
			if(event.target.value == 1) {
				this.formSelectData = '축제'
			} else if (event.target.value == 2) {
				this.formSelectData = '관광'
			} else if (event.target.value == 3) {
				this.formSelectData = '기타'
			}
		}
	}
}
</script>
<style lang="scss" scoped></style>