<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<AppContent class="poll-list">
		<div class="area__header">
			<h2 class="title">
				{{ title }}
			</h2>
			<h3 class="description">
				{{ description }}
			</h3>
		</div>

		<div class="area__main">
			<EmptyContent v-if="noPolls" icon="icon-polls">
				{{ t('polls', 'No polls found for this category') }}
				<template #desc>
					{{ t('polls', 'Add one or change category!') }}
				</template>
			</EmptyContent>

			<transition-group v-else
				name="list"
				tag="div"
				class="poll-list__list">
				<PollItem key="0" :header="true"
					:sort="sort" :reverse="reverse" @sort-list="setSort($event)" />
				<li is="PollItem"
					v-for="(poll, index) in sortedList"
					:key="poll.id"
					:poll="poll"
					@clone-poll="callPoll(index, poll, 'clone')" />
			</transition-group>
		</div>
		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import { AppContent, EmptyContent } from '@nextcloud/vue'
import PollItem from '../components/PollList/PollItem'
import { mapGetters } from 'vuex'
import sortBy from 'lodash/sortBy'
import LoadingOverlay from '../components/Base/LoadingOverlay'
import { emit } from '@nextcloud/event-bus'

export default {
	name: 'PollList',

	components: {
		AppContent,
		LoadingOverlay,
		PollItem,
		EmptyContent,
	},

	data() {
		return {
			isLoading: false,
			sort: 'created',
			reverse: true,
		}
	},

	computed: {
		...mapGetters({
			filteredPolls: 'polls/filtered',
		}),

		title() {
			if (this.$route.params.type === 'my') {
				return t('polls', 'My polls')
			} else if (this.$route.params.type === 'relevant') {
				return t('polls', 'Relevant polls')
			} else if (this.$route.params.type === 'public') {
				return t('polls', 'Public polls')
			} else if (this.$route.params.type === 'hidden') {
				return t('polls', 'Hidden polls')
			} else if (this.$route.params.type === 'deleted') {
				return t('polls', 'My deleted polls')
			} else if (this.$route.params.type === 'participated') {
				return t('polls', 'Participated by me')
			} else if (this.$route.params.type === 'closed') {
				return t('polls', 'Closed polls')
			} else {
				return t('polls', 'All polls')
			}
		},

		description() {
			if (this.$route.params.type === 'my') {
				return t('polls', 'Your polls (where you are the owner).')
			} else if (this.$route.params.type === 'relevant') {
				return t('polls', 'All polls which are relevant or important to you, because you are a participant or the owner or you are invited to. Without closed polls.')
			} else if (this.$route.params.type === 'public') {
				return t('polls', 'A complete list with all public polls on this site, regardless who is the owner.')
			} else if (this.$route.params.type === 'hidden') {
				return t('polls', 'All hidden polls, to which you have access.')
			} else if (this.$route.params.type === 'deleted') {
				return t('polls', 'The trash bin.')
			} else if (this.$route.params.type === 'participated') {
				return t('polls', 'All polls, where you placed a vote.')
			} else if (this.$route.params.type === 'closed') {
				return t('polls', 'All closed polls, where voting is disabled.')
			} else {
				return t('polls', 'All polls, where you have access to.')
			}
		},

		windowTitle() {
			return t('polls', 'Polls') + ' - ' + this.title
		},

		sortedList() {
			if (this.reverse) {
				return sortBy(this.filteredPolls(this.$route.params.type), this.sort).reverse()
			} else {
				return sortBy(this.filteredPolls(this.$route.params.type), this.sort)
			}
		},

		noPolls() {
			return this.sortedList.length < 1
		},

	},

	watch: {
		$route() {
			this.refreshView()
		},
	},

	mounted() {
		this.refreshView()
	},

	methods: {
		refreshView() {
			window.document.title = t('polls', 'Polls') + ' - ' + this.title
			if (!this.filteredPolls(this.$route.params.type).find(poll => {
				return poll.id === this.$store.state.poll.id
			})) {
				emit('toggle-sidebar', { open: false })
			}

		},

		setSort(payload) {
			if (this.sort === payload.sort) {
				this.reverse = !this.reverse
			} else {
				this.sort = payload.sort
				this.reverse = true
			}
		},

		callPoll(index, poll, name) {
			this.$router.push({
				name: name,
				params: {
					id: poll.id,
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
	.area__header {
		margin-left: 33px !important;
	}

	.poll-list__list {
		width: 100%;
		display: flex;
		flex-direction: column;
		flex-wrap: nowrap;
		overflow: scroll;
		padding-bottom: 14px;
	}
</style>
