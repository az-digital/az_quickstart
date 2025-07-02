module.exports.assertion = function (key, expected) {
  this.message = `Testing if drupalSettings.gtag.${key} is ${JSON.stringify(expected)}`;
  this.expected = JSON.stringify(expected);
  this.pass = (val) => {
    return val === this.expected;
  };
  this.value = (res) => JSON.stringify(res.value);
  this.command = (cb) => {
    const self = this;
    return this.api.execute(
      (selector) => drupalSettings?.gtag[selector] || null,
      [key],
      (res) => cb.call(self, res),
    );
  };
};
