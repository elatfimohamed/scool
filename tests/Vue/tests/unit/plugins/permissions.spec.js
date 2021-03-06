/* eslint-disable no-unused-expressions */
import Permissions from '../../../../../resources/tenant_js/plugins/permissions'
import { expect } from 'chai'
import { createLocalVue, mount } from '@vue/test-utils'

describe('permissions.js', () => {
  let localVue
  beforeEach(() => {
    localVue = createLocalVue()
    localVue.use(Permissions)
    expect(localVue.prototype.$can).to.be.a('function')
    expect(localVue.prototype.$cannot).to.be.a('function')
    expect(localVue.prototype.$haveRole).to.be.a('function')
  })

  it('show_update_button_when_user_is_superAdmin', () => {
    window.user = {
      id: 1,
      isSuperAdmin: true
    }

    const Component = {
      template: `
    <div>
      <span v-can="'task.update'"><button>Update</button></span>
    </div>`
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button>')
  })

  it('show_update_button_when_user_is_superAdmin_using_can', () => {
    window.user = {
      id: 1,
      isSuperAdmin: true
    }

    const Component = {
      template: `
    <div>
      <span v-if="$can('task.update')"><button>Update</button></span>
    </div>`
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button>')
  })

  it('show_update_button_when_user_is_superAdmin_using_haveRole', () => {
    window.user = {
      id: 1,
      isSuperAdmin: true
    }

    const Component = {
      template: `
    <div>
      <span v-if="$haveRole('Manager')"><button>Update</button></span>
    </div>`
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button>')
  })

  it('show_hidden_update_button_when_user_dont_have_permission_to_update_task_and_hidden_modifier_is_active', () => {
    window.user = {
      id: 1
    }

    const Component = {
      template: `
    <div>
      <span v-can.hidden="'task.update'"><button>Update</button></span>
    </div>`
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button style="display: none;">')
  })

  it('show_disabled_update_button_when_user_dont_have_permission_to_update_task_and_disabled_modifier_is_active', () => {
    window.user = {
      id: 1
    }
    const Component = {
      template: `
    <div>
      <span v-can.disabled="'task.update'"><button>Update</button></span>
    </div>`,
      data () {
        return {
          task: {
            id: 1,
            name: 'Comprar pa',
            completed: false,
            user_id: 45
          }
        }
      }
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button disabled="">')
  })

  it('show_update_button_when_user_have_permission_to_update_task', () => {
    window.user = {
      id: 1,
      permissions: [
        'task.update'
      ]
    }
    const Component = {
      template: `
    <div>
      <span v-can="'task.update'"><button>Update</button></span>
    </div>`
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button>')

    // expect(wrapper.find('button').isVisible()).to.be.true
  })

  it('show_update_button_when_user_have_permission_to_update_task_data', () => {
    window.user = {
      id: 1,
      permissions: [
        'task.update'
      ]
    }
    const Component = {
      template: `
    <div>
      <span v-can="permission"><button>Update</button></span>
    </div>`,
      data () {
        return {
          task: {
            id: 1,
            name: 'Comprar pa',
            completed: false,
            user_id: 45
          },
          permission: 'task.update'
        }
      }
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button>')

    // expect(wrapper.find('button').isVisible()).to.be.true
  })

  it('show_update_button_when_user_can_update_task_because_task_is_owned', () => {
    window.user = {
      id: 1
    }

    const Component = {
      template: `
    <div>
      <span v-can:update="task"><button>Update</button></span>
    </div>`,
      data () {
        return {
          task: {
            id: 1,
            name: 'Comprar pa',
            completed: false,
            user_id: 1
          }
        }
      }
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button>')
  })

  it('not_shows_update_button_when_user_not_owns_the_task_and_dont_have_permissions_to_update_task', () => {
    window.user = {
      id: 1
    }
    const Component = {
      template: `
    <div>
      <span v-can:update="task"><button>Update</button></span>
    </div>`,
      data () {
        return {
          task: {
            id: 1,
            name: 'Comprar pa',
            completed: false,
            user_id: 45
          }
        }
      }
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.not.have.string('<button>')
  })

  it('not_shows_update_button_when_user_does_not_exists', () => {
    const Component = {
      template: `
    <div>
      <span v-can:update="task"><button>Update</button></span>
    </div>`,
      data () {
        return {
          task: {
            id: 1,
            name: 'Comprar pa',
            completed: false,
            user_id: 45
          }
        }
      }
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.not.have.string('<button>')
  })

  it('shows_update_button_if_user_have_correct_role', () => {
    window.user = {
      id: 1,
      roles: [ 'Manager' ]
    }
    const Component = {
      template: `
    <div>
      <span v-role="Manager"><button>Update</button></span>
    </div>`
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button>')
  })

  it('not_shows_update_button_if_user_have_incorrect_role', () => {
    window.user = {
      id: 1,
      roles: [ 'Student' ]
    }
    const Component = {
      template: `
    <div>
      <span v-role="Manager"><button>Update</button></span>
    </div>`
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).not.to.have.string('<button>')
  })

  it('not_shows_update_button_if_user_have_incorrect_role', () => {
    window.user = {
      id: 1,
      roles: [ 'Student' ]
    }
    const Component = {
      template: `
    <div>
      <span v-role="Manager"><button>Update</button></span>
    </div>`
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).not.to.have.string('<button>')
  })

  it('not_shows_update_button_if_user_doesnt_have_roles_data', () => {
    window.user = {
      id: 1,
      roles: [ 'Student' ]
    }
    const Component = {
      template: `
    <div>
      <span v-role="role"><button>Update</button></span>
    </div>`,
      data () {
        return {
          role: 'Manager'
        }
      }
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).not.to.have.string('<button>')
  })

  it('shows_update_button_if_user_have_correct_role_data', () => {
    window.user = {
      id: 1,
      roles: [ 'Manager' ]
    }
    const Component = {
      template: `
    <div>
      <span v-role="role"><button>Update</button></span>
    </div>`,
      data () {
        return {
          role: 'Manager'
        }
      }
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button>')
  })

  it('shows_update_button_if_role_is_null', () => {
    window.user = {
      id: 1,
      roles: [ 'Manager' ]
    }
    const Component = {
      template: `
    <div>
      <span v-role="role"><button>Update</button></span>
    </div>`,
      data () {
        return {
          role: null
        }
      }
    }
    const wrapper = mount(Component, { localVue })
    expect(wrapper.html()).to.have.string('<button>')
  })
})
