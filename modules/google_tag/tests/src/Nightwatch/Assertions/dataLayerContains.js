module.exports.assertion = function (expected) {
  this.message = `Testing if dataLayer contains ${JSON.stringify(expected)}`;
  this.expected = JSON.stringify(expected);
  this.pass = (val) => {
    return (
      JSON.parse(val).filter((item) => {
        return this.expected === JSON.stringify(item);
      }).length > 0
    );
  };
  this.value = (res) => {
    const val = res.value;
    if (!Array.isArray(val)) {
      return JSON.stringify([]);
    }
    return JSON.stringify(val);
  };
  this.command = (cb) => {
    const self = this;
    return this.api.execute(
      () => window.dataLayer || [],
      [],
      (res) => cb.call(self, res),
    );
  };
};
