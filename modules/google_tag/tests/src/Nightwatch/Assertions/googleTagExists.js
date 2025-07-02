GoogleTagExists = function () {
  this.message = `Testing the GoogleTag library has loaded`;
  this.expected = function () {
    return true;
  };
  this.pass = function (value) {
    return value !== 'undefined';
  };
  this.value = (result) => {
    return result.value;
  };
  this.command = function (callback) {
    const self = this;
    return this.api.execute(
      function () {
        return typeof gtag;
      },
      [],
      function (result) {
        callback.call(self, result);
      },
    );
  };
};
module.exports.assertion = GoogleTagExists;
