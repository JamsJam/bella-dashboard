export default [
  {
    files: ['assets/**/*.js'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
    },
    rules: {
      semi: ['error', 'always'],
      quotes: ['error', 'single'],
      indent: ['warn', 4],
    },
  },
];