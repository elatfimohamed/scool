<template>
    <form>
        <!--TODO Cal ? -->
        <v-switch v-if="existing"
                :label="newPerson ? 'Nova usuari' : 'Escollir una persona existent'"
                v-model="newPerson"
        ></v-switch>
        <v-container v-else fluid grid-list-md text-xs-center>
            <v-layout row wrap>
                <v-flex md3>
                    <v-text-field
                            label="Nom"
                            v-model="givenName"
                            :error-messages="givenNameErrors"
                            @input="$v.givenName.$touch()"
                            @blur="givenNameBlur()"
                            tabindex="1"
                            required
                    ></v-text-field>
                </v-flex>
                <v-flex md3>
                    <v-text-field
                            label="Cognom1"
                            v-model="sn1"
                            :error-messages="sn1Errors"
                            @input="$v.sn1.$touch()"
                            @blur="sn1Blur()"
                            tabindex="2"
                            required
                    ></v-text-field>
                </v-flex>
                <v-flex md3>
                    <v-text-field
                            label="Cognom2"
                            v-model="sn2"
                            @blur="sn2Blur()"
                            required
                            tabindex="3"
                    ></v-text-field>
                </v-flex>
                <v-flex md3>
                    <v-text-field
                            label="Identificador (TODO DNI )"
                            v-model="identifier"
                            readonly
                            required
                    ></v-text-field>
                </v-flex>
                <v-flex md3>
                    <v-text-field
                            :label="'Correu electrònic personal (no utilitzeu ' + tenant.email_domain +')'"
                            v-model="personalEmail"
                            :error-messages="emailErrors"
                            @input="inputEmail()"
                            @blur="personalEmailBlur()"
                            required
                            tabindex="4"
                    ></v-text-field>
                </v-flex>
                <v-flex md1>
                    <v-text-field
                            label="Telèfon mòbil"
                            v-model="mobile"
                            required
                            tabindex="5"
                    ></v-text-field>
                </v-flex>
                <v-flex md2>
                    TODO
                </v-flex>
                <v-flex md2>
                    TODO
                </v-flex>
                <v-flex md2>
                    TODO
                </v-flex>
                <v-flex md2>
                    TODO
                </v-flex>
            </v-layout>
        </v-container>

        <v-btn v-if="!newPerson" color="primary" @click="select">Seleccionar</v-btn>
        <template v-else>
            <v-btn v-if="!user" color="primary" @click.native="create" :disabled="creating" :loading="creating">Crear usuari</v-btn>
            <template v-else>
                <v-btn color="error" @click.native="remove" :disabled="deleting" :loading="deleting">Eliminar</v-btn>
                <v-btn color="primary" @click.native="$emit('created', this.user)">Continuar</v-btn>
            </template>
        </template>
    </form>
</template>

<script>
import axios from 'axios'
import { validationMixin } from 'vuelidate'
import withSnackbar from '../mixins/withSnackbar'
import { required, email } from 'vuelidate/lib/validators'
import * as actions from '../../store/action-types'
import hasTenantInfo from '../mixins/hasTenantInfo'

export default {
  mixins: [validationMixin, withSnackbar, hasTenantInfo],
  name: 'PersonalDataform',
  validations: {
    givenName: { required },
    sn1: { required },
    personalEmail: { required, email }
  },
  data () {
    return {
      user: null,
      identifier: '',
      givenName: '',
      sn1: '',
      sn2: '',
      email: '',
      personalEmail: '',
      mobile: '',
      password: '',
      errors: [],
      creating: false,
      deleting: false,
      checkingPersonalEmail: false,
      newPerson: true
    }
  },
  props: {
    existing: {
      type: Boolean,
      default: false
    },
    users: {
      type: Array,
      required: true
    },
    userType: {
      required: false
    },
    role: {
      required: false
    }
  },
  computed: {
    clear () {
      this.givenName = ''
      this.sn1 = ''
      this.sn2 = ''
      this.email = ''
    },
    name () {
      return (this.givenName.trim() + ' ' + this.sn1.trim() + ' ' + this.sn2.trim()).trim()
    },
    givenNameErrors () {
      const givenNameErrors = []
      if (!this.$v.givenName.$dirty) return givenNameErrors
      !this.$v.givenName.required && givenNameErrors.push('El nom és obligatori.')
      this.errors['givenName'] && givenNameErrors.push(this.errors['givenName'])
      return givenNameErrors
    },
    sn1Errors () {
      const sn1Errors = []
      if (!this.$v.sn1.$dirty) return sn1Errors
      !this.$v.sn1.required && sn1Errors.push('El 1r cognom és obligatori.')
      this.errors['sn1'] && sn1Errors.push(this.errors['sn1'])
      return sn1Errors
    },
    emailErrors () {
      const emailErrors = []
      if (!this.$v.personalEmail.$dirty) return emailErrors
      !this.$v.personalEmail.email && emailErrors.push('El correu electrònic ha de ser vàlid')
      !this.$v.personalEmail.required && emailErrors.push('El correu electrònic és obligatori.')
      this.errors['email'] && emailErrors.push(this.errors['email'])
      return emailErrors
    }
  },
  methods: {
    remove () {
      this.removing = true
      if (this.user) {
        this.$store.dispatch(actions.DELETE_USER_PERSON, this.user.id).then(response => {
          this.removing = false
          this.user = response.data
          this.$v.$reset()
          this.$emit('deleted', this.user)
          this.showMessage('Usuari eliminat correctament')
        }).catch(error => {
          this.removing = false
          this.showError(error)
        }).then(() => {
          this.removing = false
        })
      }
    },
    inputEmail () {
      this.errors['email'] = ''
      this.$v.personalEmail.$touch()
    },
    personalEmailBlur () {
      this.$v.personalEmail.$touch()
      if (!this.$v.personalEmail.$error) this.checkPersonalEmail()
    },
    checkPersonalEmail () {
      this.checkingPersonalEmail = true
      if (this.personalEmail) {
        axios.get('/api/v1/users/email/' + this.personalEmail).then(response => {
          this.checkingPersonalEmail = false
          this.username = response.data
          this.email = this.username + '@' + window.tenant.email_domain
          this.showMessage('Ja existeix un usuari amb aquest correu electrònic')
        }).catch(error => {
          this.checkingPersonalEmail = false
          if (error.status !== 404) this.showError(error)
        })
      }
    },
    formatName (name) {
      const lc = name.toLowerCase()
      return lc.charAt(0).toUpperCase() + lc.slice(1)
    },
    givenNameBlur () {
      this.$v.givenName.$touch()
      this.givenName = this.formatName(this.givenName)
    },
    sn1Blur () {
      this.$v.sn1.$touch()
      this.sn1 = this.formatName(this.sn1)
    },
    sn2Blur () {
      this.sn2 = this.formatName(this.sn2)
      this.checkUsername()
    },
    select () {
      if (this.user) this.$emit('created', this.user)
      else this.showError('Cal seleccionar un usuari!')
    },
    getFamilyName (sn1, sn2) {
      return sn2 ? sn1 + ' ' + sn2 : sn1
    },
    create () {
      console.log('CREATE')
      this.$v.$touch()
      // if (this.personalEmail.endsWith(this.tenant.email_domain)) {
      //   this.showError('Cal indicar un email personal, no es pot utilitzar el domini ' + this.tenant.email_domain)
      //   return
      // }
      // if (!this.$v.$invalid) {
      //   this.creating = true
      //   this.$store.dispatch(actions.STORE_USER_PERSON, {
      //     givenName: this.givenName,
      //     sn1: this.sn1,
      //     sn2: this.sn2,
      //     email: this.personalEmail,
      //     mobile: this.mobile,
      //     user_type_id: this.userType,
      //     role: this.role
      //   }).then(response => {
      //     this.creating = false
      //     this.user = response.data
      //     this.$v.$reset()
      //     this.welcomeEmail && this.sendWelcomeEmail(this.user)
      //     this.googleUser && this.createGoogleUser()
      //     this.ldapUser && this.createLdapUser(this.user)
      //     this.$emit('created', this.user)
      //   }).catch(error => {
      //     if (error && error.status === 422) {
      //       this.errors = error.data && error.data.errors
      //       this.creating = false
      //       this.showError(error)
      //     }
      //   }).then(() => {
      //     this.creating = false
      //   })
      // }
    }
  }
}
</script>
