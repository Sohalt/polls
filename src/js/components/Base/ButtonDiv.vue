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

<template lang="html">
	<Component :is="tag" :class="['button', icon, { withIcon: withIcon, primary: primary } ]" @click="$emit('click')">
		{{ title }}
	</Component>
</template>

<script>
export default {
	name: 'ButtonDiv',
	props: {
		title: {
			type: String,
			default: '',
		},
		icon: {
			type: String,
			default: '',
		},
		primary: {
			type: Boolean,
			default: false,
		},
		tag: {
			type: String,
			default: 'button',
		},
	},

	computed: {
		withIcon() {
			return Boolean(this.icon)
		},
	},
}
</script>

<style lang="scss" scoped>
	.button {
		display: inline-block;
		overflow: hidden;
		text-overflow: ellipsis;

		&.withIcon {
			padding-left: 34px;
			background-position: 12px center;
		}

	}

</style>
