var ibDam = function () {

  const EMBED_TYPE = 'embed';
  const LOCAL_TYPE = 'local';

  function isEmpty(obj) {
    if (obj === null || obj === undefined) {
      return true;
    }
    for(var prop in obj) {
      if(obj.hasOwnProperty(prop))
        return false;
    }

    return obj == '' ? true : false;
  }

  function flattenProperties(item) {
    return Object.keys(item).map(function (k) { return k + ': ' + item[k] })
      .join(", ");
  }

  return {
    isEmpty: isEmpty,
    flattenProperties: flattenProperties,
    sourceTypes : {
      embed : EMBED_TYPE,
      local : LOCAL_TYPE,
    }
  };

}();
