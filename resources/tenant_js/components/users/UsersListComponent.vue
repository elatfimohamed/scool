<template>
    <v-container fluid grid-list-md text-xs-center>
        <v-layout row wrap>
            <v-flex xs12>
                <v-toolbar color="blue darken-3">
                    <v-menu bottom>
                        <v-btn slot="activator" icon dark>
                            <v-icon>more_vert</v-icon>
                        </v-btn>

                        <v-list>
                            <v-list-tile>
                                <v-list-tile-title>TODO: llista d'usuaris AAAAAAAA</v-list-tile-title>
                            </v-list-tile>
                        </v-list>
                    </v-menu>
                    <v-toolbar-title class="white--text title">Usuaris</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn icon class="white--text" @click="settings">
                        <v-icon>settings</v-icon>
                    </v-btn>
                    <v-btn icon class="white--text" @click="refresh" :disabled="refreshing" :loading="refreshing">
                        <v-icon>refresh</v-icon>
                    </v-btn>
                </v-toolbar>

                <v-card>
                    <v-card-text class="px-0 mb-2">
                        <v-card>
                            <v-card-title>
                                <v-spacer></v-spacer>
                                <v-text-field
                                        append-icon="search"
                                        label="Buscar"
                                        single-line
                                        hide-details
                                        v-model="search"
                                ></v-text-field>
                            </v-card-title>
                            <v-data-table
                                class="px-0 mb-2 hidden-sm-and-down"
                                :headers="headers"
                                :items="internalUsers"
                                :search="search"
                                item-key="id"
                                disable-initial-sort
                                no-results-text="No s'ha trobat cap registre coincident"
                                no-data-text="No hi han dades disponibles"
                                rows-per-page-text="Usuaris per pàgina"
                                :rows-per-page-items="[5,10,25,50,100,200,500,1000,{'text':'Tots','value':-1}]"
                            >
                            <template slot="items" slot-scope="{item: user}">
                                <tr>
                                    <td class="text-xs-left">
                                        {{ user.id }}
                                    </td>
                                    <td>
                                        <user-avatar :hash-id="user.hashid"
                                                     :alt="user.name"
                                                     :user="user"
                                                     :editable="true"
                                                     :removable="true"
                                        ></user-avatar>
                                    </td>
                                    <td class="text-xs-left">
                                        {{ user.name }}
                                    </td>
                                    <td class="text-xs-left">{{ user.email }}</td>
                                    <td class="text-xs-left">{{ formatBoolean(user.email_verified_at) }}</td>
                                    <td class="text-xs-left">
                                        <template v-if="user.corporativeEmail">
                                            <a target="_blank" :href="'https://admin.google.com/u/3/ac/users/' + user.googleId">{{ user.corporativeEmail }}</a>
                                        </template>
                                        <manage-corporative-email-icon :user="user" @unassociated="refresh" @associated="refresh" @added="refresh"></manage-corporative-email-icon>
                                    </td>
                                    <td class="text-xs-left">{{ user.mobile }}</td>
                                    <td class="text-xs-left">
                                        <v-tooltip bottom>
                                            <span slot="activator">{{ user.last_login }}</span>
                                            <span>{{ user.last_login_ip }}</span>
                                        </v-tooltip>
                                    </td>
                                    <td class="text-xs-left" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <v-tooltip bottom>
                                            <span slot="activator">{{ formatRoles(user) }}</span>
                                            <span>{{ formatRoles(user) }}</span>
                                        </v-tooltip>
                                    </td>
                                    <td class="text-xs-left">{{ user.formatted_created_at }}</td>
                                    <td class="text-xs-left">{{ user.formatted_updated_at }}</td>
                                    <td class="text-xs-left">
                                        <show-user-icon :user="user" :users="users"></show-user-icon>

                                        <confirm-icon icon="email"
                                                      :working="sendingWelcomeEmail"
                                                      @confirmed="sendWelcomeEmail(user)"
                                                      tooltip="(Re)Enviar email de benvinguda"
                                                      message="Esteu segurs que voleu enviar email de benvinguda a l'usuari?"
                                                      confirm="Enviar"
                                        ></confirm-icon>

                                        <confirm-icon icon="email"
                                                      :working="sendingResetPassword"
                                                      @confirmed="sendResetPasswordEmail(user)"
                                                      tooltip="Enviar email restauració paraula de pas"
                                                      message="Esteu segurs que voleu enviar email per canviar paraula de pas?"
                                                      confirm="Enviar"
                                        ></confirm-icon>

                                        <confirm-icon v-show="!user.email_verified_at" icon="email"
                                                      :working="sendingEmailConfirmation"
                                                      @confirmed="sendEmailConfirmation(user)"
                                                      tooltip="Enviar email confirmació email"
                                                      message="Esteu segurs que voleu enviar email per confirmar email de l'usuari?"
                                                      confirm="Enviar"
                                        ></confirm-icon>
                                        <v-btn icon class="mx-0" @click="editItem(user)">
                                            <v-icon color="teal">edit</v-icon>
                                        </v-btn>
                                        <v-btn icon class="mx-0" @click="showConfirmationDialog(user)">
                                            <v-icon color="pink">delete</v-icon>
                                            <v-dialog v-model="showDeleteUserDialog" max-width="500px">
                                                <v-card>
                                                    <v-card-text>
                                                        Esteu segurs que voleu eliminar aquest usuari?
                                                    </v-card-text>
                                                    <v-card-actions>
                                                        <v-btn flat @click.stop="showDeleteUserDialog=false">Cancel·lar</v-btn>
                                                        <v-btn color="primary" @click.stop="deleteUser" :loading="deleting">Esborrar</v-btn>
                                                    </v-card-actions>
                                                </v-card>
                                            </v-dialog>
                                        </v-btn>
                                    </td>
                                </tr>
                            </template>
                            </v-data-table>
                        </v-card>

                        <v-data-iterator
                                class="hidden-md-and-up"
                                content-tag="v-layout"
                                row
                                wrap
                                :items="internalUsers"
                        >
                            <v-flex
                                    slot="item"
                                    slot-scope="props"
                                    xs12
                                    sm6
                                    md4
                                    lg3
                            >
                                <v-card>
                                    <v-card-title><h4>{{ props.item.name }}</h4></v-card-title>
                                    <v-divider></v-divider>
                                    <v-list dense>
                                        <v-list-tile>
                                            <v-list-tile-content>Email:</v-list-tile-content>
                                            <v-list-tile-content class="align-end">{{ props.item.email }}</v-list-tile-content>
                                        </v-list-tile>
                                        <v-list-tile>
                                            <v-list-tile-content>Created at:</v-list-tile-content>
                                            <v-list-tile-content class="align-end">{{ props.item.created_at }}</v-list-tile-content>
                                        </v-list-tile>
                                        <v-list-tile>
                                            <v-list-tile-content>Updated at:</v-list-tile-content>
                                            <v-list-tile-content class="align-end">{{ props.item.updated_at }}</v-list-tile-content>
                                        </v-list-tile>
                                    </v-list>
                                </v-card>
                            </v-flex>
                        </v-data-iterator>
                    </v-card-text>
                </v-card>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
import { mapGetters } from 'vuex'
import * as mutations from '../../store/mutation-types'
import * as actions from '../../store/action-types'
import withSnackbar from '../mixins/withSnackbar'
import ConfirmIcon from '../ui/ConfirmIconComponent.vue'
import ShowUserIcon from './ShowUserIconComponent.vue'
import SendsWelcomeEmail from './mixins/SendsWelcomeEmail'
import UserAvatar from '../ui/UserAvatarComponent'
import ManageCorporativeEmailIcon from '../google/users/ManageCorporativeEmailIcon'
import axios from 'axios'

export default {
  mixins: [withSnackbar, SendsWelcomeEmail],
  components: {
    'confirm-icon': ConfirmIcon,
    'show-user-icon': ShowUserIcon,
    'manage-corporative-email-icon': ManageCorporativeEmailIcon,
    'user-avatar': UserAvatar
  },
  data () {
    return {
      showDeleteUserDialog: false,
      search: '',
      deleting: false,
      refreshing: false,
      headers: [
        { text: 'Id', align: 'left', value: 'id' },
        { text: 'Avatar', value: 'photo', sortable: false },
        { text: 'Name', value: 'name' },
        { text: 'Email', value: 'email' },
        { text: 'Verificat', value: 'email_verified_at' },
        { text: 'Email corporatiu', value: 'corporativeEmail' },
        { text: 'Mòbil', value: 'mobile' },
        { text: 'Últim login', value: 'last_login' },
        { text: 'Rols', value: 'roles', sortable: false },
        { text: 'Data creació', value: 'created_at_timestamp' },
        { text: 'Data actualització', value: 'updated_at_timestamp' },
        { text: 'Accions', sortable: false }
      ],
      sendingResetPassword: false,
      sendingEmailConfirmation: false
    }
  },
  props: {
    users: {
      type: Array,
      required: false
    }
  },
  computed: {
    ...mapGetters({
      internalUsers: 'users'
    })
  },
  methods: {
    formatBoolean (boolean) {
      return boolean ? 'Sí' : 'No'
    },
    refresh () {
      this.refreshing = true
      this.$store.dispatch(actions.FETCH_USERS).then(response => {
        this.refreshing = false
        this.showMessage('Usuaris actualizats correctament')
      }).catch(error => {
        this.refreshing = false
        this.showError(error)
      })
    },
    settings () {
      console.log('settings TODO') // TODO
    },
    sendResetPasswordEmail (user) {
      this.sendingResetPassword = true
      axios.post('password/email', {
        email: user.email
      }).then(response => {
        this.sendingResetPassword = false
        this.showMessage(`Correu electrònic enviat correctament`)
      }).catch(error => {
        this.showError(error)
      })
    },
    sendEmailConfirmation (user) {
      this.sendingEmailConfirmation = true
      this.$store.dispatch(actions.CONFIRM_USER_EMAIL, user).then(response => {
        this.showMessage(`Correu electrònic enviat per tal de confirmar el email`)
      }).catch(error => {
        console.dir(error)
        this.showError(error)
      }).then(() => {
        this.sendingEmailConfirmation = false
      })
    },
    formatRoles (user) {
      return Object.values(user.roles).join(', ')
    },
    showConfirmationDialog (user) {
      this.currentUser = user
      this.showDeleteUserDialog = true
    },
    deleteUser () {
      this.deleting = true
      this.$store.dispatch(actions.DELETE_USER, this.currentUser).then(response => {
        this.deleting = false
        this.showDeleteUserDialog = false
      }).catch(error => {
        this.showError(error)
        this.deleting = false
      }).then(() => {
        this.deleting = false
      })
    }
  },
  created () {
    this.$store.commit(mutations.SET_USERS, this.users)
  }
}
</script>
