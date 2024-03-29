/* 기본셋팅 1204 최정훈 */
import { createWebHistory, createRouter } from 'vue-router';
import MainComponent from '../components/MainComponent.vue';
import LoginComponent from '../components/LoginComponent.vue';
import SigninComponent from '../components/SigninComponent.vue';
import UserComponent from '../components/UserComponent.vue';
import RegionComponent from '../components/RegionComponent.vue';
import AuthComponent from '../components/AuthComponent.vue';
import AdminComponent from '../components/AdminComponent.vue';
import DetailComponent from '../components/DetailComponent.vue';
import UserChk from '../components/UserChk.vue';
import ErrorComponent from '../components/ErrorComponent.vue';
// 3차 프로젝트를 위한 라우터
import BoardComponent from '../components/BoardComponent.vue';
import QnaComponent from '../components/QnaComponent.vue';
import PostDetailComponent from '../components/PostDetailComponent.vue';
import PostWriteComponent from '../components/PostWriteComponent.vue';
import CommunityComponent from '../components/CommunityComponent.vue';
import KakaoCallback from '../components/KakaoCallback.vue';
import WriteComponent from '../components/WriteComponent.vue';
import store from './store.js'
import VueCookies from "vue-cookies";
const routes = [
	{
		path: "/",
		redirect : '/main'
	},
	{
		path: "/main",
		component : MainComponent
	},
	{
		path: "/error",
		component : ErrorComponent
	},
	{
		path: '/region',
		component : RegionComponent
	},
	{
		path: "/detail",
		component : DetailComponent
	},
	{
		path: "/board",
		component : BoardComponent
	},
	{
		path: "/qna",
		component : QnaComponent
	},
	{
		path: "/post",
		component : PostDetailComponent
	},
	{
		path: "/write",
		component : PostWriteComponent
	},
	{
		path: "/community",
		component : CommunityComponent
	},
	{
		path: "/kakaologin",
		component : KakaoCallback
	},
	{
		path: "/commuwrite",
		component : WriteComponent
	},
	{
		path: "/login",
		component: LoginComponent,
		beforeEnter: (to, from, next) => {
			if (store.state.localFlg) {
				next('/');
			} else {
				next();
			}
		},
	},
	{
		path: "/admin",
		component: AdminComponent,
		beforeEnter: (to, from, next) => {
			if (!localStorage.getItem('admin')) {
				next('/');
			} else {
				next();
			}
		},
	},
	{
		path: "/authemail",
		component : AuthComponent,
		beforeEnter: (to, from, next) => {
			if (store.state.localFlg) {
				next('/');
			} else {
				next();
			}
		},
	},
	{
		path: "/signin",
		component : SigninComponent,
		beforeEnter: (to, from, next) => {
			if (!(VueCookies.get('auth_id'))) {
				next('/');
			} else {
				next();
			}
		},
	},
	{
		path: "/userchk",
		component : UserChk,
		// 1211 최정훈 추가 유저페이지는 로그인 했을때만 이동가능
		beforeEnter: (to, from, next) => {
			if (!store.state.localFlg) {
				console.log('routes : userchk > /');
				next('/');
			} else {
				next();
			}
		}		
	},
	{
		path: "/user",
		component : UserComponent,
		// 1211 최정훈 추가 유저페이지는 로그인 했을때만 이동가능
		beforeEnter: (to, from, next) => {
			if (!store.state.userFlg) {
				console.log('routes : user > userchk');
				next('/userchk');
			} else {
				next();
			}
		}		
	},
];

const router = createRouter({
	history: createWebHistory(),
	routes,
});

router.beforeEach((to, from, next) => {
	// 모드페이지 라우트시 페이지 제일위로
	store.state.beforeUrl = window.location.pathname + window.location.search;
	window.scrollTo(0, 0); 
	next();
  });

export default router;