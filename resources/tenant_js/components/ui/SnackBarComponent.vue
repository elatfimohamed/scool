<template>
    <v-snackbar
            :timeout="snackbarTimeout"
            :color="snackbarColor"
            v-model="snackbarShow"
            :multi-line="true"
    >
        {{ snackbarText }}<br/>
        {{ snackbarSubtext }}
        <v-btn dark flat @click.native="close">Tancar</v-btn>
    </v-snackbar>
</template>

<script>
import { mapGetters } from 'vuex'
import * as actions from '../../store/action-types'

export default {
  computed: {
    ...mapGetters([
      'snackbarShow',
      'snackbarColor',
      'snackbarText',
      'snackbarSubtext',
      'snackbarTimeout'
    ])
  },
  methods: {
    close () {
      this.$store.dispatch(actions.SET_SNACKBAR_SHOW, false)
    }
  }
}
</script>
