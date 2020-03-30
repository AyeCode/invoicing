const path = require('path');
const VueLoaderPlugin = require('vue-loader/lib/plugin');

module.exports = {
	mode: "production",
	entry: {
		"field-editor": "./assets/js/src/field-editor.js",
	},
	output: {
		filename: "[name].js",
		path: path.resolve(__dirname, "./assets/js/dist")
	},
	module: {
		rules: [
			{
				test: /\.vue$/,
				loader: 'vue-loader'
			},

			{
				test: /\.js$/,
				exclude: /(node_modules|bower_components)/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: ['@babel/preset-env'],
						cacheDirectory: true
					}
				}
			}
		]
	},
	plugins: [
		new VueLoaderPlugin()
	]
};
