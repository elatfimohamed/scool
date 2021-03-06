/* eslint-disable no-undef,no-unused-expressions */
import { expect } from 'chai'

export default {
  assertContains: function (selector) {
    expect(this.contains(selector)).to.be.true
  },
  assertNotContains: function (selector) {
    expect(this.contains(selector)).to.be.false
  },
  assertVisible: function (selector) {
    expect(this.find(selector).isVisible()).to.be.true
  },
  assertNotVisible: function (selector) {
    expect(this.find(selector).isVisible()).to.be.true
  },
  seeHtml: function (text, selector) {
    let wrap = selector ? this.find(selector) : this
    expect(wrap.html()).contains(text)
  },
  seeText: function (text, selector) {
    let wrap = selector ? this.find(selector) : this
    expect(wrap.text()).contains(text)
  },
  assertEmitted: function (event) {
    const eventObj = this.emitted()[event]
    expect(eventObj).not.to.be.an('undefined')
    return eventObj
  },
  assertNotEmitted: function (event) {
    expect(this.emitted()[event]).to.be.an('undefined')
  },
  assertEventContains: function (event, key, value) {
    expect(this.emitted()[event][key]).toBe(value)
  },
  type: function (selector, text) {
    let node = this.find(selector)
    node.element.value = text
    node.trigger('input')
  },
  click: function (selector) {
    this.find(selector).trigger('click')
  }
}
