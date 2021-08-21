module.exports = {
  parser: 'postcss-scss',
  plugins: [
    require('postcss-advanced-variables')(),
    require('postcss-nested')(),
    require('autoprefixer')(),
    require('cssnano')({
      preset: 'default',
    }),
  ],
};