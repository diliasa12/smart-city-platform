const catchAsync = (controller) => async (req, res) => {
  try {
    await controller(req, res, next);
  } catch (error) {
    return next(error);
  }
};
export default catchAsync;
